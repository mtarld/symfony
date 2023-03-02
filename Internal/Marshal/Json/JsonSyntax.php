<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Marshal\Json;

use Symfony\Component\Marshaller\Internal\Marshal\Node\ArrayAccessNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\BinaryNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\FunctionNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ScalarNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\VariableNode;
use Symfony\Component\Marshaller\Internal\Marshal\NodeInterface;
use Symfony\Component\Marshaller\Internal\Marshal\SyntaxInterface;

final class JsonSyntax implements SyntaxInterface
{
    public function startDictString(): string
    {
        return '{';
    }

    public function endDictString(): string
    {
        return '}';
    }

    public function startDictKeyString(): string
    {
        return '"';
    }

    public function endDictKeyString(): string
    {
        return '"';
    }

    public function startListString(): string
    {
        return '[';
    }

    public function endListString(): string
    {
        return ']';
    }

    public function collectionItemSeparatorString(): string
    {
        return ',';
    }

    public function escapeString(string $string): string
    {
        return json_encode($string) ?: $string;
    }

    public function escapeNode(NodeInterface $node): NodeInterface
    {
        return new FunctionNode('\json_encode', [
            $node,
            new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('json_encode_flags')), new ScalarNode(0)),
        ]);
    }
}
