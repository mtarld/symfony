<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template\Json;

use Symfony\Component\Marshaller\Native\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
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

    protected function beforeKey(): string
    {
        return '';
    }

    protected function afterKey(): string
    {
        return ':';
    }

    protected function itemSeparator(): string
    {
        return ',';
    }

    protected function escapeKey(NodeInterface $key): NodeInterface
    {
        return new FunctionNode('\json_encode', [$key]);
    }
}
