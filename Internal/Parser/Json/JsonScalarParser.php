<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Parser\Json;

use Symfony\Component\Marshaller\Internal\Parser\ScalarParserInterface;
use Symfony\Component\Marshaller\Internal\Type\Type;

final class JsonScalarParser implements ScalarParserInterface
{
    public function parse(\Iterator $tokens, Type $type, array $context): int|float|string|bool|null
    {
        $value = \json_decode($tokens->current(), flags: $context['json_decode_flags'] ?? 0);
        $tokens->next();

        return $value;
    }
}
