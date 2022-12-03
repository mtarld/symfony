<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template;

use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Node\AssignNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Native\Ast\Node\PropertyNode;
use Symfony\Component\Marshaller\Native\Ast\Node\RawNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Native\Hook\HookExtractor;
use Symfony\Component\Marshaller\Native\Type\Type;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;

/**
 * @internal
 */
abstract class ObjectTemplateGenerator
{
    use VariableNameScoperTrait;

    private readonly HookExtractor $hookExtractor;
    private readonly ReflectionTypeExtractor $reflectionTypeExtractor;

    public function __construct(
        private readonly TemplateGeneratorInterface $templateGenerator,
    ) {
        $this->hookExtractor = new HookExtractor();
        $this->reflectionTypeExtractor = new ReflectionTypeExtractor();
    }

    abstract protected function beforeProperties(): string;

    abstract protected function afterProperties(): string;

    abstract protected function propertySeparator(): string;

    abstract protected function beforePropertyName(): string;

    abstract protected function afterPropertyName(): string;

    abstract protected function escapeString(string $string): string;

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    public function generate(Type $type, NodeInterface $accessor, array $context): array
    {
        $class = new \ReflectionClass($type->className());
        $objectName = $this->scopeVariableName('object', $context);

        $nodes = [
            new ExpressionNode(new AssignNode(new VariableNode($objectName), $accessor)),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->beforeProperties())])),
        ];

        $propertySeparator = '';

        foreach ($class->getProperties() as $property) {
            if (!$property->isPublic()) {
                throw new \RuntimeException(sprintf('"%s::$%s" must be public.', $class->getName(), $property->getName()));
            }

            $propertyName = $property->getName();
            $propertyType = $this->reflectionTypeExtractor->extractFromProperty($property);
            $propertyAccessor = new PropertyNode(new VariableNode($objectName), $property->getName());
            $propertyContext = $context;

            if (null !== $hook = $this->hookExtractor->extractFromProperty($property, $context)) {
                [$propertyName, $propertyType, $propertyAccessor, $propertyContext] = $this->callPropertyHook($hook, $property, $propertyAccessor, $context);
            }

            \array_push(
                $nodes,
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($propertySeparator)])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->beforePropertyName())])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->escapeString($propertyName))])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->afterPropertyName())])),
                ...$this->templateGenerator->generate(Type::createFromString($propertyType), $propertyAccessor, $propertyContext),
            );

            $propertySeparator = $this->propertySeparator();
        }

        $nodes[] = new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->afterProperties())]));

        return $nodes;
    }

    /**
     * @param callable(\ReflectionProperty, string, array<string, mixed>): array{name?: string, type?: string, accessor?: string, context?: array<string, mixed>} $hook
     * @param array<string, mixed>                                                                                                                                $context
     *
     * @return array{0: string, 1: string, 2: RawNode, 3: array<string, mixed>}
     */
    private function callPropertyHook(callable $hook, \ReflectionProperty $property, NodeInterface $accessor, array $context): array
    {
        $hookResult = $hook($property, (new Compiler())->compile($accessor)->source(), $context);

        if (!isset($hookResult['name'])) {
            throw new \RuntimeException('Hook array result is missing "name".');
        }

        if (!is_string($hookResult['name'])) {
            throw new \RuntimeException('Hook array result\'s "name" must be a "string".');
        }

        if (!isset($hookResult['type'])) {
            throw new \RuntimeException('Hook array result is missing "type".');
        }

        if (!is_string($hookResult['type'])) {
            throw new \RuntimeException('Hook array result\'s "type" must be a "string".');
        }

        if (!isset($hookResult['accessor'])) {
            throw new \RuntimeException('Hook array result is missing "accessor".');
        }

        if (!is_string($hookResult['accessor'])) {
            throw new \RuntimeException('Hook array result\'s "accessor" must be a "string".');
        }

        if (!isset($hookResult['context'])) {
            throw new \RuntimeException('Hook array result is missing "context".');
        }

        if (!is_array($hookResult['context'])) {
            throw new \RuntimeException('Hook array result\'s "context" must be an "array".');
        }

        return [
            $hookResult['name'],
            $hookResult['type'],
            new RawNode($hookResult['accessor']),
            $hookResult['context'],
        ];
    }
}
