<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Parser\Json;

use Symfony\Component\Marshaller\Internal\Exception\InvalidTokenException;
use Symfony\Component\Marshaller\Internal\Parser\DictParserInterface;

/**
 * @internal
 */
final class JsonDictParser implements DictParserInterface
{
    public function parse(\Iterator $tokens, array $context): \Iterator
    {
        if ('{' !== $tokens->current()) {
            throw new InvalidTokenException('{', $tokens->current());
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
                $key = \json_decode($token, flags: $context['json_decode_flags'] ?? 0);
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
