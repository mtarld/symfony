<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template\Json;

use Symfony\Component\Marshaller\Native\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\TernaryConditionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Native\Template\ScalarTemplateGenerator;
use Symfony\Component\Marshaller\Native\Type\Type;

/**
 * @internal
 */
final class JsonScalarTemplateGenerator extends ScalarTemplateGenerator
{
    protected function scalar(Type $type, NodeInterface $accessor, array $context): array
    {
        if ('string' === $type->name()) {
            return [
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('"')])),
                new ExpressionNode(new FunctionNode('\fwrite', [
                    new VariableNode('resource'),
                    new FunctionNode('\addcslashes', [$accessor, new ScalarNode('"\0\t\"\$\\\"', escaped: false)]),
                ])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('"')])),
            ];
        }

        if ('bool' === $type->name()) {
            return [
                new ExpressionNode(new FunctionNode('\fwrite', [
                    new VariableNode('resource'),
                    new TernaryConditionNode($accessor, new ScalarNode(true), new ScalarNode(false)),
                ])),
            ];
        }

        return [
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), $accessor])),
        ];
    }
}
