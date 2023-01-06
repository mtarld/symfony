<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Lexer;

use Symfony\Component\Marshaller\Internal\Exception\InvalidJsonException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class JsonLexer implements LexerInterface
{
    private const DICT_START = 1;
    private const DICT_END = 2;

    private const LIST_START = 4;
    private const LIST_END = 8;

    private const KEY = 16;
    private const COLUMN = 32;
    private const COMMA = 64;

    private const SCALAR = 128;

    private const END = 256;

    private const VALUE = self::DICT_START | self::LIST_START | self::SCALAR;

    public function tokens(mixed $resource, array $context): \Iterator
    {
        $expectedType = self::VALUE;
        $structureStack = new \SplStack();

        foreach ($this->tokenize($resource) as [$type, $token]) {
            if ('' === $token) {
                continue;
            }

            if (!($type & $expectedType)) {
                throw new InvalidJsonException();
            }

            if (self::SCALAR === $type) {
                // TODO maybe yield the result for performances?
                json_decode($token, flags: $context['json_decode_flags'] ?? 0);

                if (\JSON_ERROR_NONE !== json_last_error()) {
                    throw new InvalidJsonException();
                }
            }

            if (self::KEY === $type && !(str_starts_with($token, '"') && str_ends_with($token, '"'))) {
                throw new InvalidJsonException();
            }

            yield $token;

            if (self::DICT_START === $type) {
                $structureStack->push('dict');
            } elseif (self::LIST_START === $type) {
                $structureStack->push('list');
            } elseif ($type & (self::DICT_END | self::LIST_END)) {
                $structureStack->pop();
            }

            $currentStructure = !$structureStack->isEmpty() ? $structureStack->top() : null;

            $expectedType = match (true) {
                self::DICT_START === $type => self::KEY | self::DICT_END,
                self::LIST_START === $type => self::VALUE | self::LIST_END,

                self::KEY === $type => self::COLUMN,
                self::COLUMN === $type => self::VALUE,

                self::COMMA === $type && 'dict' === $currentStructure => self::KEY,
                self::COMMA === $type && 'list' === $currentStructure => self::VALUE,

                0 !== ($type & (self::DICT_END | self::LIST_END | self::SCALAR)) && 'dict' === $currentStructure => self::COMMA | self::DICT_END,
                0 !== ($type & (self::DICT_END | self::LIST_END | self::SCALAR)) && 'list' === $currentStructure => self::COMMA | self::LIST_END,
                0 !== ($type & (self::DICT_END | self::LIST_END | self::SCALAR)) => self::END,

                default => throw new InvalidJsonException(),
            };
        }

        if (self::END !== $expectedType) {
            throw new InvalidJsonException();
        }
    }

    /**
     * @param resource $resource
     *
     * @return \Generator<array{int, string}>
     */
    private function tokenize(mixed $resource): \Generator
    {
        $token = '';
        $inString = false;
        $escaping = false;

        while (!feof($resource)) {
            if (false === $buffer = stream_get_contents($resource, 4096)) {
                throw new \RuntimeException('Cannot read JSON resource.');
            }

            $length = \strlen($buffer);

            for ($i = 0; $i < $length; ++$i) {
                $byte = $buffer[$i];

                if ($escaping) {
                    $escaping = false;
                    $token .= $byte;

                    continue;
                }

                if ($inString) {
                    $token .= $byte;

                    if ('"' === $byte) {
                        $inString = false;
                    } elseif ('\\' === $byte) {
                        $escaping = true;
                    }

                    continue;
                }

                if ('"' === $byte) {
                    $token .= $byte;
                    $inString = true;

                    continue;
                }

                if (',' === $byte) {
                    yield [self::SCALAR, $token];
                    yield [self::COMMA, $byte];

                    $token = '';

                    continue;
                }

                if (':' === $byte) {
                    yield [self::KEY, $token];
                    yield [self::COLUMN, $byte];

                    $token = '';

                    continue;
                }

                if ('{' === $byte) {
                    yield [self::SCALAR, $token];
                    yield [self::DICT_START, $byte];

                    $token = '';

                    continue;
                }

                if ('[' === $byte) {
                    yield [self::SCALAR, $token];
                    yield [self::LIST_START, $byte];

                    $token = '';

                    continue;
                }

                if ('}' === $byte) {
                    yield [self::SCALAR, $token];
                    yield [self::DICT_END, $byte];

                    $token = '';

                    continue;
                }

                if (']' === $byte) {
                    yield [self::SCALAR, $token];
                    yield [self::LIST_END, $byte];

                    $token = '';

                    continue;
                }

                if ('' === $token && \in_array($byte, [' ', "\r", "\t", "\n"], true)) {
                    continue;
                }

                $token .= $byte;
            }
        }

        if (!$inString && !$escaping) {
            yield [self::SCALAR, $token];
        }
    }
}
