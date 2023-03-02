<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Marshal;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
interface SyntaxInterface
{
    public function startDictString(): string;

    public function endDictString(): string;

    public function startDictKeyString(): string;

    public function endDictKeyString(): string;

    public function startListString(): string;

    public function endListString(): string;

    public function collectionItemSeparatorString(): string;

    public function escapeString(string $string): string;

    public function escapeNode(NodeInterface $node): NodeInterface;
}
