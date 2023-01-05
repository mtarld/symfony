<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Parser\Json;

use Symfony\Component\Marshaller\Internal\Parser\NullableParserInterface;

final class JsonNullableParser implements NullableParserInterface
{
    public function parse(\Iterator $tokens, callable $handle, array $context): mixed
    {
        $token = $tokens->current();

        if ('null' === $token) {
            $tokens->next();

            return null;
        }

        return $handle($tokens);
    }
}
