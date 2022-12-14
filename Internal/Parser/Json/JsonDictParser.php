<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Parser\Json;

use Symfony\Component\Marshaller\Internal\Parser\DictParserInterface;
use Symfony\Component\Marshaller\Internal\Parser\Parser;
use Symfony\Component\Marshaller\Internal\Type\Type;
use Symfony\Component\Marshaller\Internal\Type\UnionType;

final class JsonDictParser implements DictParserInterface
{
    public function parse(\Iterator $tokens, Type|UnionType $valueType, array $context, Parser $parser): array
    {
        if ('{' !== $tokens->current()) {
            throw new \InvalidArgumentException('Invalid JSON.');
        }

        $buffer = [];
        $result = [];
        $level = 0;
        $key = null;

        $tokens->next();

        while ($tokens->valid()) {
            $token = $tokens->current();
            $tokens->next();

            // TODO check null key and null value
            if (0 === $level && '}' === $token) {
                if (null !== $key) {
                    $result[$key] = $parser->parse(new \ArrayIterator($buffer), $valueType, $context);
                }

                return $result;
            }

            if (null === $key) {
                // TODO flags

                /** @var string $key */
                $key = \json_decode($token);

                continue;
            }

            if (':' === $token) {
                continue;
            }

            if (0 === $level && ',' === $token) {
                $result[$key] = $parser->parse(new \ArrayIterator($buffer), $valueType, $context);
                $key = null;
                $buffer = [];

                continue;
            }

            $buffer[] = $token;

            if ('{' === $token) {
                ++$level;
            } elseif ('}' === $token) {
                --$level;
            }
        }

        return $result;
    }

    public function parseIterable(\Iterator $tokens, Type|UnionType $valueType, array $context, Parser $parser): iterable
    {
        if ('{' !== $tokens->current()) {
            throw new \InvalidArgumentException('Invalid JSON.');
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
                // TODO flags
                $key = \json_decode($token);
                $tokens->next();

                continue;
            }

            if (\in_array($token, [',', ':'], true)) {
                $tokens->next();

                continue;
            }

            yield $key => $parser->parse($tokens, $valueType, $context);

            $key = null;
        }
    }
}
