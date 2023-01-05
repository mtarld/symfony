<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Parser;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
interface NullableParserInterface
{
    /**
     * @param \Iterator<string>                  $tokens
     * @param callable(\Iterator<string>): mixed $handle
     * @param array<string, mixed>               $context
     */
    public function parse(\Iterator $tokens, callable $handle, array $context): mixed;
}
