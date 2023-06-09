<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Internal\Serialize\TemplateGenerator;

use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Internal\Serialize\Compiler;
use Symfony\Component\Serializer\Internal\Serialize\Node\AssignNode;
use Symfony\Component\Serializer\Internal\Serialize\Node\BinaryNode;
use Symfony\Component\Serializer\Internal\Serialize\Node\ExpressionNode;
use Symfony\Component\Serializer\Internal\Serialize\Node\FunctionNode;
use Symfony\Component\Serializer\Internal\Serialize\Node\IfNode;
use Symfony\Component\Serializer\Internal\Serialize\Node\PropertyNode;
use Symfony\Component\Serializer\Internal\Serialize\Node\RawNode;
use Symfony\Component\Serializer\Internal\Serialize\Node\ScalarNode;
use Symfony\Component\Serializer\Internal\Serialize\Node\UnaryNode;
use Symfony\Component\Serializer\Internal\Serialize\Node\VariableNode;
use Symfony\Component\Serializer\Internal\Serialize\NodeInterface;
use Symfony\Component\Serializer\Internal\Serialize\VariableNameScoperTrait;
use Symfony\Component\Serializer\Type\ReflectionTypeExtractor;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\Serializer\Type\TypeSorter;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
abstract class TemplateGenerator
{
    use VariableNameScoperTrait;

    public function __construct(
        protected readonly ReflectionTypeExtractor $reflectionTypeExtractor,
        protected readonly TypeSorter $typeSorter,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    abstract protected function nullNodes(array $context): array;

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    abstract protected function scalarNodes(Type $type, NodeInterface $accessor, array $context): array;

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    abstract protected function listNodes(Type $type, NodeInterface $accessor, array $context): array;

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    abstract protected function dictNodes(Type $type, NodeInterface $accessor, array $context): array;

    /**
     * @param array<string, array{name: string, type: Type, accessor: NodeInterface}> $properties
     * @param array<string, mixed>                                                    $context
     *
     * @return list<NodeInterface>
     */
    abstract protected function objectNodes(Type $type, array $properties, array $context): array;

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    abstract protected function mixedNodes(NodeInterface $accessor, array $context): array;

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    final public function generate(Type $type, NodeInterface $accessor, array $context): array
    {
        if (!$type->isNullable()) {
            return $this->nodes($type, $accessor, $context);
        }

        return [
            new IfNode(
                new BinaryNode('===', new ScalarNode(null), $accessor),
                $this->nullNodes($context),
                $this->nodes($type, $accessor, $context),
            ),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    private function nodes(Type $type, NodeInterface $accessor, array $context): array
    {
        if ($type->isUnion()) {
            $unionTypes = $this->typeSorter->sortByPrecision($type->unionTypes());

            if (1 === \count($unionTypes)) {
                return $this->generate($unionTypes[0], $accessor, $context);
            }

            /** @var Type $ifType */
            $ifType = array_shift($unionTypes);

            /** @var Type $elseType */
            $elseType = array_pop($unionTypes);

            /** @var list<array{condition: NodeInterface, body: list<NodeInterface>}> $elseIfTypes */
            $elseIfTypes = array_map(fn (Type $t): array => ['condition' => $this->typeValidatorNode($t, $accessor), 'body' => $this->generate($t, $accessor, $context)], $unionTypes);

            return [new IfNode(
                $this->typeValidatorNode($ifType, $accessor),
                $this->generate($ifType, $accessor, $context),
                $this->generate($elseType, $accessor, $context),
                $elseIfTypes,
            )];
        }

        if ($type->isNull()) {
            return $this->nullNodes($context);
        }

        if ($type->isScalar()) {
            return $this->scalarNodes($type, $accessor, $context);
        }

        if ($type->isEnum()) {
            return $this->scalarNodes($type, new PropertyNode($accessor, 'value'), $context);
        }

        if ($type->isList()) {
            return $this->listNodes($type, $accessor, $context);
        }

        if ($type->isDict()) {
            return $this->dictNodes($type, $accessor, $context);
        }

        if ($type->isObject()) {
            try {
                $className = $type->className();
            } catch (LogicException) {
                return $this->mixedNodes($accessor, $context);
            }

            $objectName = $this->scopeVariableName('object', $context);
            $class = new \ReflectionClass($className);
            $properties = [];

            foreach ($class->getProperties() as $property) {
                if (!$property->isPublic()) {
                    throw new LogicException(sprintf('"%s::$%s" must be public.', $class->getName(), $property->getName()));
                }

                $properties[$property->getName()] = [
                    'name' => $property->getName(),
                    'type' => $this->reflectionTypeExtractor->extractFromProperty($property),
                    'accessor' => new PropertyNode(new VariableNode($objectName), $property->getName()),
                ];
            }

            if (null !== $hook = $context['hooks']['serialize'][$className] ?? $context['hooks']['serialize']['object'] ?? null) {
                /** @var array{properties?: array<string, array{name: string, type: Type, accessor: string}>, context?: array<string, mixed>} $hookResult */
                $hookResult = $hook(
                    $type,
                    (new Compiler())->compile(new VariableNode($objectName))->source(),
                    array_map(fn (array $p): array => ['accessor' => (new Compiler())->compile($p['accessor'])->source()] + $p, $properties),
                    $context,
                );

                $context = $hookResult['context'] ?? $context;

                if (isset($hookResult['properties'])) {
                    $properties = array_map(fn (array $p): array => [
                        'accessor' => new RawNode($p['accessor']),
                        'type' => $p['type'],
                    ] + $p, $hookResult['properties']);
                }
            }

            if (isset($context['generated_classes'][$className])) {
                throw new CircularReferenceException($className);
            }

            $context['generated_classes'][$className] = true;

            return [
                new ExpressionNode(new AssignNode(new VariableNode($objectName), $accessor)),
                ...$this->objectNodes($type, $properties, $context),
            ];
        }

        return $this->mixedNodes($accessor, $context);
    }

    protected function typeValidatorNode(Type $type, NodeInterface $accessor): NodeInterface
    {
        return match (true) {
            $type->isNull() => new BinaryNode('===', new ScalarNode(null), $accessor),
            $type->isScalar() => new FunctionNode(sprintf('\is_%s', $type->name()), [$accessor]),
            $type->isList() => new BinaryNode('&&', new FunctionNode('\is_array', [$accessor]), new FunctionNode('\array_is_list', [$accessor])),
            $type->isDict() => new BinaryNode('&&', new FunctionNode('\is_array', [$accessor]), new UnaryNode('!', new FunctionNode('\array_is_list', [$accessor]))),
            $type->isObject() => new BinaryNode('instanceof', $accessor, new ScalarNode($type->className())),
            'array' === $type->name() => new FunctionNode('\is_array', [$accessor]),
            'iterable' === $type->name() => new FunctionNode('\is_iterable', [$accessor]),
            'mixed' === $type->name() => new ScalarNode(true),
            default => throw new LogicException(sprintf('Cannot find validator for "%s".', (string) $type)),
        };
    }
}
