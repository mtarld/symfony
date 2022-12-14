<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Parser\Json;

use Symfony\Component\Marshaller\Internal\Parser\NullableParserInterface;
use Symfony\Component\Marshaller\Internal\Parser\Parser;
use Symfony\Component\Marshaller\Internal\Type\Type;

final class JsonNullableParser implements NullableParserInterface
{
    public function parse(\Iterator $tokens, Type $type, array $context, Parser $parser): mixed
    {
        $token = $tokens->current();

        if ('null' === $token) {
            $tokens->next();

            return null;
        }

        $type = Type::createFromString(substr((string) $type, 1));

        return $parser->parse($tokens, $type, $context);
    }
}
