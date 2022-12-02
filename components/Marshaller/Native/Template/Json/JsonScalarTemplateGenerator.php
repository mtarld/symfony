<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template\Json;

use Symfony\Component\Marshaller\Native\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
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
        return [
            new ExpressionNode(new FunctionNode('\fwrite', [
                new VariableNode('resource'),
                new FunctionNode('\json_encode', [$accessor]),
            ])),
        ];
    }
}
