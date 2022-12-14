<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Parser\Json;

use Symfony\Component\Marshaller\Internal\Parser\ListParserInterface;
use Symfony\Component\Marshaller\Internal\Parser\Parser;
use Symfony\Component\Marshaller\Internal\Type\Type;
use Symfony\Component\Marshaller\Internal\Type\UnionType;

final class JsonListParser implements ListParserInterface
{
    public function parse(\Iterator $tokens, Type|UnionType $valueType, array $context, Parser $parser): array
    {
        if ('[' !== $tokens->current()) {
            throw new \InvalidArgumentException('Invalid JSON.');
        }

        $buffer = [];
        $result = [];
        $level = 0;

        $tokens->next();

        while ($tokens->valid()) {
            $token = $tokens->current();
            $tokens->next();

            // TODO check null value
            if (0 === $level && ',' === $token) {
                $result[] = $parser->parse(new \ArrayIterator($buffer), $valueType, $context);

                return $result;
            }

            if (0 === $level && ']' === $token) {
                $result[] = $parser->parse(new \ArrayIterator($buffer), $valueType, $context);
                $buffer = [];

                continue;
            }

            $buffer[] = $token;

            if ('[' === $token) {
                ++$level;

                continue;
            }

            if (']' === $token) {
                --$level;

                continue;
            }
        }

        return $result;
    }

    public function parseIterable(\Iterator $tokens, Type|UnionType $valueType, array $context, Parser $parser): iterable
    {
        if ('[' !== $tokens->current()) {
            throw new \InvalidArgumentException('Invalid JSON.');
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

            yield $parser->parse($tokens, $valueType, $context);
        }
    }
}
