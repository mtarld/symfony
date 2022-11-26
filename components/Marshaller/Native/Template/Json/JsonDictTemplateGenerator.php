<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template\Json;

use Symfony\Component\Marshaller\Native\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Template\DictTemplateGenerator;

/**
 * @internal
 */
final class JsonDictTemplateGenerator extends DictTemplateGenerator
{
    protected function beforeItems(): string
    {
        return '{';
    }

    protected function afterItems(): string
    {
        return '}';
    }

    protected function keyValueSeparator(): string
    {
        return ':';
    }

    protected function itemSeparator(): string
    {
        return ',';
    }

    protected function escapeKey(NodeInterface $key): NodeInterface
    {
        return new FunctionNode('\addcslashes', [$key, new ScalarNode('"\0\t\"\$\\\"', escaped: false)]);
    }
}
