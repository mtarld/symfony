<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Internal\Serialize\TemplateGenerator;

use Symfony\Component\SerDes\Exception\CircularReferenceException;
use Symfony\Component\SerDes\Exception\LogicException;
use Symfony\Component\SerDes\Internal\Serialize\Compiler;
use Symfony\Component\SerDes\Internal\Serialize\Node\AssignNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\BinaryNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ExpressionNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\FunctionNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\IfNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\PropertyNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\RawNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ScalarNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\UnaryNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\VariableNode;
use Symfony\Component\SerDes\Internal\Serialize\NodeInterface;
use Symfony\Component\SerDes\Internal\Serialize\VariableNameScoperTrait;
use Symfony\Component\SerDes\Type\ReflectionTypeExtractor;
use Symfony\Component\SerDes\Type\Type;
use Symfony\Component\SerDes\Type\TypeFactory;
use Symfony\Component\SerDes\Type\TypeSorter;
use Symfony\Component\SerDes\Type\UnionType;

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
     * @param array<string, array{name: string, type: Type|UnionType, accessor: NodeInterface}> $properties
     * @param array<string, mixed>                                                              $context
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
    final public function generate(Type|UnionType $type, NodeInterface $accessor, array $context): array
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
    private function nodes(Type|UnionType $type, NodeInterface $accessor, array $context): array
    {
        if ($type instanceof UnionType) {
            if (\count($type->types) <= 0) {
                return [];
            }

            $types = $this->typeSorter->sortByPrecision($type->types);

            if (1 === \count($types)) {
                return $this->generate($types[0], $accessor, $context);
            }

            /** @var Type $ifType */
            $ifType = array_shift($types);

            /** @var Type $elseType */
            $elseType = array_pop($types);

            /** @var list<array{condition: NodeInterface, body: list<NodeInterface>}> $elseIfTypes */
            $elseIfTypes = array_map(fn (Type $t): array => ['condition' => $this->typeValidatorNode($t, $accessor), 'body' => $this->generate($t, $accessor, $context)], $types);

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
                /** @var array{properties?: array<string, array{name?: string, type?: Type|UnionType, accessor?: string}>, context?: array<string, mixed>} $hookResult */
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

    private function typeValidatorNode(Type $type, NodeInterface $accessor): NodeInterface
    {
        // TODO test is_iterable
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
