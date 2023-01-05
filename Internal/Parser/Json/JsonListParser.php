<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Parser\Json;

use Symfony\Component\Marshaller\Internal\Exception\InvalidTokenException;
use Symfony\Component\Marshaller\Internal\Parser\ListParserInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class JsonListParser implements ListParserInterface
{
    public function parse(\Iterator $tokens, array $context): \Iterator
    {
        if ('[' !== $tokens->current()) {
            throw new InvalidTokenException('[', $tokens->current());
        }

        $tokens->next();

        while ($tokens->valid()) {
            $token = $tokens->current();

            if (']' === $token) {
                $tokens->next();

                return;
            }

            if (',' === $token) {
                $tokens->next();

                continue;
            }

            yield;
        }
    }
}
