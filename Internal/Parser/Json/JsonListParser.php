<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Parser\Json;

use Symfony\Component\Marshaller\Exception\UnexpectedTokenException;
use Symfony\Component\Marshaller\Internal\Parser\ListParserInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class JsonListParser implements ListParserInterface
{
    public function parse(\Iterator $tokens, $resource, array $context): \Iterator
    {
        // $it = new \LimitIterator($tokens->);
        dd($resource);
        dd($it->current());


        if ('[' !== $tokens->current()) {
            throw new UnexpectedTokenException('[', $tokens->current());
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
