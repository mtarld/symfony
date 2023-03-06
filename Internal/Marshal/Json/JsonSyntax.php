<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Marshal\Json;

use Symfony\Component\Marshaller\Exception\RuntimeException;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ArrayAccessNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\BinaryNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\FunctionNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ScalarNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\VariableNode;
use Symfony\Component\Marshaller\Internal\Marshal\NodeInterface;
use Symfony\Component\Marshaller\Internal\Marshal\SyntaxInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
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
        return '":';
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
        $encoded = json_encode($string);
        if (false === $encoded) {
            throw new RuntimeException(sprintf('Cannot encode "%s"', $string));
        }

        return substr($encoded, 1, -1);
    }

    public function escapeStringNode(NodeInterface $node): NodeInterface
    {
        return new FunctionNode('\substr', [
            new FunctionNode('\json_encode', [
                $node,
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('json_encode_flags')), new ScalarNode(0)),
            ]),
            new ScalarNode(1),
            new ScalarNode(-1),
        ]);
    }

    public function encodeValueNode(NodeInterface $node): NodeInterface
    {
        return new FunctionNode('\json_encode', [
            $node,
            new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('json_encode_flags')), new ScalarNode(0)),
        ]);
    }
}
