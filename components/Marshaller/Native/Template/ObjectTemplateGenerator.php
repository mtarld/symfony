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
            new ExpressionNode(new FunctionNode('\fwrite', [new ScalarNode($this->beforeProperties())])),
        ];

        $propertySeparator = '';

        foreach ($class->getProperties() as $property) {
            if (!$property->isPublic()) {
                throw new \RuntimeException(sprintf('"%s::$%s" must be public.', $class->getName(), $property->getName()));
            }

            $propertyName = $property->getName();
            $propertyType = $this->reflectionTypeExtractor->extractFromProperty($property);
            $propertyAccessor = new PropertyNode(new VariableNode($objectName), $property->getName());

            if (null !== $hook = $this->hookExtractor->extractFromProperty($property, $context)) {
                $hookResult = $hook($property, (new Compiler())->compile($propertyAccessor)->source(), $context);

                $propertyName = $hookResult['name'];
                $propertyType = $hookResult['type'];
                $propertyAccessor = new RawNode($hookResult['accessor']);
                $context = $hookResult['context'];
            }

            \array_push(
                $nodes,
                new ExpressionNode(new FunctionNode('\fwrite', [new ScalarNode($propertySeparator)])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->beforePropertyName())])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->escapeString($propertyName), escaped: false)])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->afterPropertyName())])),
                ...$this->templateGenerator->generate(Type::createFromString($propertyType), $propertyAccessor, $context),
            );

            $propertySeparator = $this->propertySeparator();
        }

        $nodes[] = new ExpressionNode(new FunctionNode('\fwrite', [new ScalarNode($this->afterProperties())]));

        return $nodes;
    }
}
