<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template;

use Symfony\Component\Marshaller\Native\Ast\Node\AssignNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ForEachNode;
use Symfony\Component\Marshaller\Native\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\TemplateStringNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Native\Type\Type;

/**
 * @internal
 */
abstract class DictTemplateGenerator
{
    use VariableNameScoperTrait;

    public function __construct(
        private readonly TemplateGeneratorInterface $templateGenerator,
    ) {
    }

    abstract protected function beforeItems(): string;

    abstract protected function afterItems(): string;

    abstract protected function itemSeparator(): string;

    abstract protected function beforeKey(): string;

    abstract protected function afterKey(): string;

    abstract protected function escapeKey(NodeInterface $key): NodeInterface;

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    public function generate(Type $type, NodeInterface $accessor, array $context): array
    {
        $prefixName = $this->scopeVariableName('prefix', $context);
        $keyName = $this->scopeVariableName('key', $context);
        $valueName = $this->scopeVariableName('value', $context);

        return [
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->beforeItems())])),
            new ExpressionNode(new AssignNode(new VariableNode($prefixName), new ScalarNode(''))),

            new ForEachNode($accessor, $keyName, $valueName, [
                new ExpressionNode(new AssignNode(new VariableNode($keyName), $this->escapeKey(new VariableNode($keyName)))),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new TemplateStringNode(
                    new VariableNode($prefixName),
                    $this->beforeKey(),
                    new VariableNode($keyName),
                    $this->afterKey(),
                )])),
                ...$this->templateGenerator->generate($type->collectionValueType(), new VariableNode($valueName), $context),
                new ExpressionNode(new AssignNode(new VariableNode($prefixName), new ScalarNode($this->itemSeparator()))),
            ]),

            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->afterItems())])),
        ];
    }
}
