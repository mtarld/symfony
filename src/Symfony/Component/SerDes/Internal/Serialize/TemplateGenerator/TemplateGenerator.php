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
use Symfony\Component\SerDes\Internal\Serialize\TypeSorter;
use Symfony\Component\SerDes\Internal\Serialize\VariableNameScoperTrait;
use Symfony\Component\SerDes\Internal\Type;
use Symfony\Component\SerDes\Internal\TypeFactory;
use Symfony\Component\SerDes\Internal\UnionType;
use Symfony\Component\SerDes\Type\ReflectionTypeExtractor;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
abstract class TemplateGenerator
{
    use VariableNameScoperTrait;

    public function __construct(
        private readonly ReflectionTypeExtractor $reflectionTypeExtractor,
        private readonly TypeSorter $typeSorter,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    abstract protected function initialClosuresNodes(array $context): array;

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
     * @param list<array{name: string, type: string, accessor: NodeInterface, context: array<string, mixed>}> $propertiesInfo
     * @param array<string, mixed>                                                                            $context
     *
     * @return list<NodeInterface>
     */
    abstract protected function objectNodes(Type $type, array $propertiesInfo, array $context): array;

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
    public function generate(Type|UnionType $type, NodeInterface $accessor, array $context): array
    {
        $nodes = !($context['closure_generated'] ?? false) ? $this->initialClosuresNodes($context) : [];
        $context['closure_generated'] = true;

        if (!$type->isNullable()) {
            return [
                ...$nodes,
                ...$this->nodes($type, $accessor, $context),
            ];
        }

        return [
            ...$nodes,
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

            if (null !== $hook = $context['hooks']['serialize'][$className] ?? $context['hooks']['serialize']['object'] ?? null) {
                $hookResult = $hook((string) $type, (new Compiler())->compile($accessor)->source(), $context);

                /** @var Type $type */
                $type = isset($hookResult['type']) ? TypeFactory::createFromString($hookResult['type']) : $type;
                $accessor = isset($hookResult['accessor']) ? new RawNode($hookResult['accessor']) : $accessor;
                $context = $hookResult['context'] ?? $context;
            }

            if (isset($context['generated_classes'][$className])) {
                throw new CircularReferenceException($className);
            }

            $context['generated_classes'][$className] = true;

            $objectName = $this->scopeVariableName('object', $context);

            $propertiesInfo = $this->computePropertiesInfo($type->className(), $objectName, $context);

            return [
                new ExpressionNode(new AssignNode(new VariableNode($objectName), $accessor)),
                ...$this->objectNodes($type, $propertiesInfo, $context),
            ];
        }

        return $this->mixedNodes($accessor, $context);
    }

    /**
     * @param class-string         $className
     * @param array<string, mixed> $context
     *
     * @return list<array{name: string, type: string, accessor: NodeInterface, context: array<string, mixed>}>
     */
    private function computePropertiesInfo(string $className, string $objectAccessor, array $context): array
    {
        $class = new \ReflectionClass($className);

        $propertiesInfo = [];

        foreach ($class->getProperties() as $property) {
            if (!$property->isPublic()) {
                throw new LogicException(sprintf('"%s::$%s" must be public.', $class->getName(), $property->getName()));
            }

            $propertyName = $property->getName();
            $propertyType = $this->reflectionTypeExtractor->extractFromProperty($property);
            $propertyAccessor = new PropertyNode(new VariableNode($objectAccessor), $property->getName());
            $propertyContext = $context;

            if (null !== $hook = $context['hooks']['serialize'][$className.'::$'.$propertyName] ?? $context['hooks']['serialize']['property'] ?? null) {
                $hookResult = $hook($property, (new Compiler())->compile($propertyAccessor)->source(), $context);

                if (\array_key_exists('accessor', $hookResult) && null === $hookResult['accessor']) {
                    continue;
                }

                $propertyName = $hookResult['name'] ?? $propertyName;
                $propertyType = $hookResult['type'] ?? $propertyType;
                $propertyAccessor = isset($hookResult['accessor']) ? new RawNode($hookResult['accessor']) : $propertyAccessor;
                $propertyContext = $hookResult['context'] ?? $propertyContext;
            }

            $propertiesInfo[] = ['name' => $propertyName, 'type' => $propertyType, 'accessor' => $propertyAccessor, 'context' => $propertyContext];
        }

        return $propertiesInfo;
    }

    private function typeValidatorNode(Type $type, NodeInterface $accessor): NodeInterface
    {
        if ($type->isNull()) {
            return new BinaryNode('===', new ScalarNode(null), $accessor);
        }

        if ($type->isScalar()) {
            return new FunctionNode(sprintf('\is_%s', $type->name()), [$accessor]);
        }

        if ($type->isList()) {
            return new BinaryNode('&&', new FunctionNode('\is_array', [$accessor]), new FunctionNode('\array_is_list', [$accessor]));
        }

        if ($type->isDict()) {
            return new BinaryNode('&&', new FunctionNode('\is_array', [$accessor]), new UnaryNode('!', new FunctionNode('\array_is_list', [$accessor])));
        }

        if ($type->isObject()) {
            return new BinaryNode('instanceof', $accessor, new ScalarNode($type->className()));
        }

        if ('array' === $type->name()) {
            return new FunctionNode('\is_array', [$accessor]);
        }

        if ('mixed' === $type->name()) {
            return new ScalarNode(true);
        }

        throw new LogicException(sprintf('Cannot find validator for "%s".', (string) $type));
    }
}
