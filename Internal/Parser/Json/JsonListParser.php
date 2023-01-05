<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Parser\Json;

use Symfony\Component\Marshaller\Internal\Exception\InvalidTokenException;
use Symfony\Component\Marshaller\Internal\Parser\ListParserInterface;

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
