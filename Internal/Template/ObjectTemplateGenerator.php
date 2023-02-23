<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Template;

use Symfony\Component\Marshaller\Exception\LogicException;
use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Node\AssignNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Internal\Ast\Node\PropertyNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\RawNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Internal\Hook\HookExtractor;
use Symfony\Component\Marshaller\Internal\Type\Type;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class ObjectTemplateGenerator
{
    use VariableNameScoperTrait;

    private readonly HookExtractor $hookExtractor;
    private readonly ReflectionTypeExtractor $reflectionTypeExtractor;

    /**
     * @param \Closure(string): string $propertyNameEscaper
     */
    public function __construct(
        private readonly string $beforeProperties,
        private readonly string $afterProperties,
        private readonly string $propertySeparator,
        private readonly string $beforePropertyName,
        private readonly string $afterPropertyName,
        private readonly \Closure $propertyNameEscaper,
    ) {
        $this->hookExtractor = new HookExtractor();
        $this->reflectionTypeExtractor = new ReflectionTypeExtractor();
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    public function generate(Type $type, NodeInterface $accessor, array $context, TemplateGenerator $templateGenerator): array
    {
        $class = new \ReflectionClass($type->className());
        $objectName = $this->scopeVariableName('object', $context);

        $nodes = [
            new ExpressionNode(new AssignNode(new VariableNode($objectName), $accessor)),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->beforeProperties)])),
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

            if (null !== $hook = $this->hookExtractor->extractFromProperty($property, $context)) {
                $hookResult = $hook($property, (new Compiler())->compile($propertyAccessor)->source(), $context);

                $propertyName = $hookResult['name'] ?? $propertyName;
                $propertyType = $hookResult['type'] ?? $propertyType;
                $propertyAccessor = isset($hookResult['accessor']) ? new RawNode($hookResult['accessor']) : $propertyAccessor;
                $propertyContext = $hookResult['context'] ?? $propertyContext;
            }

            array_push(
                $nodes,
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($propertySeparator)])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->beforePropertyName)])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode(($this->propertyNameEscaper)($propertyName))])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->afterPropertyName)])),
                ...$templateGenerator->generate(TypeFactory::createFromString($propertyType), $propertyAccessor, $propertyContext),
            );

            $propertySeparator = $this->propertySeparator;
        }

        $nodes[] = new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->afterProperties)]));

        return $nodes;
    }
}
