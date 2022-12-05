<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Template;

use Symfony\Component\Marshaller\Internal\Ast\Node\AssignNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\ForEachNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Internal\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Internal\Type\Type;

/**
 * @internal
 */
final class ListTemplateGenerator
{
    use VariableNameScoperTrait;

    public function __construct(
        private readonly string $beforeItems,
        private readonly string $afterItems,
        private readonly string $itemSeparator,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    public function generate(Type $type, NodeInterface $accessor, array $context, TemplateGenerator $templateGenerator): array
    {
        $prefixName = $this->scopeVariableName('prefix', $context);
        $valueName = $this->scopeVariableName('value', $context);

        return [
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->beforeItems)])),
            new ExpressionNode(new AssignNode(new VariableNode($prefixName), new ScalarNode(''))),

            new ForEachNode($accessor, null, $valueName, [
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new VariableNode($prefixName)])),
                ...$templateGenerator->generate($type->collectionValueType(), new VariableNode($valueName), $context),
                new ExpressionNode(new AssignNode(new VariableNode($prefixName), new ScalarNode($this->itemSeparator))),
            ]),

            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($this->afterItems)])),
        ];
    }
}
