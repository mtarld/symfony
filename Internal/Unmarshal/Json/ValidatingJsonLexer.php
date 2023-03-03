<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Unmarshal\Json;

use Symfony\Component\Marshaller\Exception\InvalidResourceException;
use Symfony\Component\Marshaller\Internal\Unmarshal\LexerInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class ValidatingJsonLexer implements LexerInterface
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

    public function __construct(
        private readonly LexerInterface $lexer,
    ) {
    }

    /**
     * @throws InvalidResourceException
     */
    public function tokens(mixed $resource, int $offset, int $length, array $context): \Iterator
    {
        $expectedType = self::VALUE;
        $structureStack = new \SplStack();
        $isKey = new \SplStack();

        foreach ($this->lexer->tokens($resource, $offset, $length, $context) as $i => [$token, $offset]) {
            if ('{' === $token) {
                $type = self::DICT_START;
                $nextExpectedType = self::DICT_END | self::KEY;

                $structureStack->push('dict');
                $isKey->push(true);
            } elseif ('}' === $token) {
                $type = self::DICT_END;

                if ($structureStack->isEmpty() || $isKey->isEmpty()) {
                    throw new InvalidResourceException($resource);
                }

                $structureStack->pop();
                $isKey->pop();

                if ($structureStack->isEmpty()) {
                    $nextExpectedType = self::END;
                } elseif ('dict' === $structureStack->top()) {
                    if ($isKey) {
                        if (true === $isKey->top()) {
                            $nextExpectedType = self::COLUMN;
                            $isKey->pop();
                            $isKey->push(false);
                        } else {
                            $nextExpectedType = self::DICT_END | self::COMMA;
                            $isKey->pop();
                            $isKey->push(true);
                        }
                    }
                } else {
                    $nextExpectedType = self::LIST_END | self::COMMA;
                }
            } elseif ('[' === $token) {
                $type = self::LIST_START;
                $nextExpectedType = self::LIST_END | self::VALUE;

                $structureStack->push('list');
            } elseif (']' === $token) {
                $type = self::LIST_END;
                if ($structureStack->isEmpty()) {
                    throw new InvalidResourceException($resource);
                }

                $structureStack->pop();

                if ($structureStack->isEmpty()) {
                    $nextExpectedType = self::END;
                } elseif ('dict' === $structureStack->top()) {
                    if (true === $isKey->top()) {
                        $nextExpectedType = self::COLUMN;
                        $isKey->pop();
                        $isKey->push(false);
                    } else {
                        $nextExpectedType = self::DICT_END | self::COMMA;
                        $isKey->pop();
                        $isKey->push(true);
                    }
                } else {
                    $nextExpectedType = self::LIST_END | self::COMMA;
                }
            } elseif (',' === $token) {
                $type = self::COMMA;

                if ($structureStack->isEmpty()) {
                    throw new InvalidResourceException($resource);
                }

                $nextExpectedType = 'dict' === $structureStack->top() ? self::KEY : self::VALUE;
            } elseif (':' === $token) {
                $type = self::COLUMN;

                if ($structureStack->isEmpty() || 'dict' !== $structureStack->top()) {
                    throw new InvalidResourceException($resource);
                }

                $nextExpectedType = self::VALUE;
            } else {
                $this->validateScalar($resource, $token, $context);

                if ($structureStack->isEmpty()) {
                    $type = self::VALUE;
                    $nextExpectedType = self::END;
                } elseif ('dict' === $structureStack->top()) {
                    if (true === $isKey->top()) {
                        if (!(str_starts_with($token, '"') && str_ends_with($token, '"'))) {
                            throw new InvalidResourceException($resource);
                        }

                        $type = self::KEY;
                        $nextExpectedType = self::COLUMN;
                        $isKey->pop();
                        $isKey->push(false);
                    } else {
                        $type = self::VALUE;
                        $nextExpectedType = self::DICT_END | self::COMMA;
                        $isKey->pop();
                        $isKey->push(true);
                    }
                } else {
                    $type = self::VALUE;
                    $nextExpectedType = self::LIST_END | self::COMMA;
                }
            }

            if (!($type & $expectedType)) {
                throw new InvalidResourceException($resource);
            }

            $expectedType = $nextExpectedType;

            yield [$token, $offset];
        }

        if (self::END !== $expectedType) {
            throw new InvalidResourceException($resource);
        }
    }

    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     *
     * @throws InvalidResourceException
     */
    private function validateScalar(mixed $resource, string $scalar, array $context): void
    {
        try {
            json_decode($scalar, associative: true, flags: ($context['json_decode_flags'] ?? 0) | \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new InvalidResourceException($resource);
        }
    }
}
