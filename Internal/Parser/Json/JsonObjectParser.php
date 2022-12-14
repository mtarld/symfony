<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Parser\Json;

use Symfony\Component\Marshaller\Internal\Parser\ObjectParserInterface;

final class JsonObjectParser implements ObjectParserInterface
{
    public function parse(\Iterator $tokens, callable $setProperty, array $context): void
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
                    $setProperty($key, new \ArrayIterator($buffer));
                }

                return;
            }

            if (null === $key) {
                // TODO flags
                $key = \json_decode($token);

                continue;
            }

            if (':' === $token) {
                continue;
            }

            if (0 === $level && ',' === $token) {
                $setProperty($key, new \ArrayIterator($buffer));
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
    }
}
