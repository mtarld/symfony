<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Parser\Json;

use Symfony\Component\Marshaller\Exception\UnexpectedTokenException;
use Symfony\Component\Marshaller\Internal\Parser\DictParserInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class JsonDictParser implements DictParserInterface
{
    public function parse(\Iterator $tokens, array $context): \Iterator
    {
        if ('{' !== $tokens->current()) {
            throw new UnexpectedTokenException('{', $tokens->current());
        }

        $tokens->next();
        $key = null;

        while ($tokens->valid()) {
            $token = $tokens->current();

            if ('}' === $token) {
                $tokens->next();

                return;
            }

            if (null === $key) {
                $key = json_decode($token, flags: $context['json_decode_flags'] ?? 0);
                $tokens->next();

                continue;
            }

            if (',' === $token || ':' === $token) {
                $tokens->next();

                continue;
            }

            yield $key;

            $key = null;
        }
    }
}
