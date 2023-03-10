<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Marshal;

use Symfony\Component\Marshaller\Exception\CircularReferenceException;
use Symfony\Component\Marshaller\Exception\LogicException;
use Symfony\Component\Marshaller\Exception\UnsupportedTypeException;
use Symfony\Component\Marshaller\Internal\Marshal\Node\AssignNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\BinaryNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ExpressionNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ForEachNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\FunctionNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\IfNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\PropertyNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\RawNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ScalarNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\TemplateStringNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\UnaryNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\VariableNode;
use Symfony\Component\Marshaller\Internal\Type;
use Symfony\Component\Marshaller\Internal\TypeFactory;
use Symfony\Component\Marshaller\Internal\UnionType;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class TemplateGenerator
{
    use VariableNameScoperTrait;

    public function __construct(
        private readonly ReflectionTypeExtractor $reflectionTypeExtractor,
        private readonly TypeSorter $typeSorter,
        private readonly SyntaxInterface $syntax,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    public function generate(Type|UnionType $type, NodeInterface $accessor, array $context): array
    {
        $nodes = $this->nodes($type, $accessor, $context);

        if (!$type->isNullable()) {
            return $nodes;
        }

        return [
            new IfNode(
                new BinaryNode('===', new ScalarNode(null), $accessor),
                $this->scalarNodes($accessor),
                $nodes,
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
        return match (true) {
            $type instanceof UnionType => $this->unionNodes($type, $accessor, $context),
            $type->isScalar(), $type->isNull() => $this->scalarNodes($accessor),
            $type->isObject() => $this->objectNodes($type, $accessor, $context),
            $type->isList() => $this->listNodes($type, $accessor, $context),
            $type->isDict() => $this->dictNodes($type, $accessor, $context),
            default => throw new UnsupportedTypeException((string) $type),
        };
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    public function unionNodes(UnionType $type, NodeInterface $accessor, array $context): array
    {
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

    /**
     * @return list<NodeInterface>
     */
    public function scalarNodes(NodeInterface $accessor): array
    {
        return [
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), $this->syntax->encodeValueNode($accessor)])),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    public function listNodes(Type $type, NodeInterface $accessor, array $context): array
    {
        $prefixName = $this->scopeVariableName('prefix', $context);
        $valueName = $this->scopeVariableName('value', $context);

        return [
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->syntax->startListString())])),
            new ExpressionNode(new AssignNode(new VariableNode($prefixName), new ScalarNode(''))),

            new ForEachNode($accessor, null, $valueName, [
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new VariableNode($prefixName)])),
                ...$this->generate($type->collectionValueType(), new VariableNode($valueName), $context),
                new ExpressionNode(new AssignNode(new VariableNode($prefixName), new ScalarNode($this->syntax->collectionItemSeparatorString()))),
            ]),

            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->syntax->endListString())])),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    private function dictNodes(Type $type, NodeInterface $accessor, array $context): array
    {
        $prefixName = $this->scopeVariableName('prefix', $context);
        $keyName = $this->scopeVariableName('key', $context);
        $valueName = $this->scopeVariableName('value', $context);

        return [
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->syntax->startDictString())])),
            new ExpressionNode(new AssignNode(new VariableNode($prefixName), new ScalarNode(''))),

            new ForEachNode($accessor, $keyName, $valueName, [
                new ExpressionNode(new AssignNode(new VariableNode($keyName), $this->syntax->escapeStringNode(new VariableNode($keyName)))),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new TemplateStringNode(
                    new VariableNode($prefixName),
                    $this->syntax->startDictKeyString(),
                    new VariableNode($keyName),
                    $this->syntax->endDictKeyString(),
                )])),
                ...$this->generate($type->collectionValueType(), new VariableNode($valueName), $context),
                new ExpressionNode(new AssignNode(new VariableNode($prefixName), new ScalarNode($this->syntax->collectionItemSeparatorString()))),
            ]),

            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->syntax->endDictString())])),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    private function objectNodes(Type $type, NodeInterface $accessor, array $context): array
    {
        $hook = null;

        if (isset($context['hooks']['marshal'][$className = $type->className()])) {
            $hook = $context['hooks']['marshal'][$className];
        } elseif (isset($context['hooks']['marshal']['object'])) {
            $hook = $context['hooks']['marshal']['object'];
        }

        if (null !== $hook) {
            $hookResult = $hook((string) $type, (new Compiler())->compile($accessor)->source(), $context);

            /** @var Type $type */
            $type = isset($hookResult['type']) ? TypeFactory::createFromString($hookResult['type']) : $type;
            $accessor = isset($hookResult['accessor']) ? new RawNode($hookResult['accessor']) : $accessor;
            $context = $hookResult['context'] ?? $context;
        }

        $className = $type->className();

        if (isset($context['generated_classes'][$className])) {
            throw new CircularReferenceException($className);
        }

        $context['generated_classes'][$className] = true;

        $class = new \ReflectionClass($type->className());
        $objectName = $this->scopeVariableName('object', $context);

        $nodes = [
            new ExpressionNode(new AssignNode(new VariableNode($objectName), $accessor)),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->syntax->startDictString())])),
        ];

        $propertySeparator = '';

        foreach ($class->getProperties() as $property) {
            if (!$property->isPublic()) {
                throw new LogicException(sprintf('"%s::$%s" must be public.', $class->getName(), $property->getName()));
            }

            $propertyName = $property->getName();
            $propertyType = $this->reflectionTypeExtractor->extractFromProperty($property);
            $propertyAccessor = new PropertyNode(new VariableNode($objectName), $property->getName());
            $propertyContext = $context;

            $hook = null;

            if (isset($context['hooks']['marshal'][$className.'::$'.$propertyName])) {
                $hook = $context['hooks']['marshal'][$className.'::$'.$propertyName];
            } elseif (isset($context['hooks']['marshal']['property'])) {
                $hook = $context['hooks']['marshal']['property'];
            }

            if (null !== $hook) {
                $hookResult = $hook($property, (new Compiler())->compile($propertyAccessor)->source(), $context);

                $propertyName = $hookResult['name'] ?? $propertyName;
                $propertyType = $hookResult['type'] ?? $propertyType;
                $propertyAccessor = isset($hookResult['accessor']) ? new RawNode($hookResult['accessor']) : $propertyAccessor;
                $propertyContext = $hookResult['context'] ?? $propertyContext;
            }

            array_push(
                $nodes,
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($propertySeparator)])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->syntax->startDictKeyString())])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->syntax->escapeString($propertyName))])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->syntax->endDictKeyString())])),
                ...$this->generate(TypeFactory::createFromString($propertyType), $propertyAccessor, $propertyContext),
            );

            $propertySeparator = $this->syntax->collectionItemSeparatorString();
        }

        $nodes[] = new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->syntax->endDictString())]));

        return $nodes;
    }

    public function typeValidatorNode(Type $type, NodeInterface $accessor): NodeInterface
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

        throw new LogicException(sprintf('Cannot find validator for "%s".', (string) $type));
    }
}
