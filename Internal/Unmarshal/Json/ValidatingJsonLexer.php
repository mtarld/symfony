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
    /**
     * @var array<string, bool>
     */
    private static array $validScalarCache = [];

    /**
     * @var array<string, bool>
     */
    private static array $validKeyCache = [];

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

        $structureStack = [];
        $structureStackPointer = -1;

        $shouldBeDictKeyStack = [];
        $shouldBeDictKeyStackPointer = -1;

        foreach ($this->lexer->tokens($resource, $offset, $length, $context) as $i => [$token, $offset]) {
            if ('{' === $token) {
                $type = self::DICT_START;
                $nextExpectedType = self::DICT_END | self::KEY;

                ++$structureStackPointer;
                $structureStack[$structureStackPointer] = 'dict';

                ++$shouldBeDictKeyStackPointer;
                $shouldBeDictKeyStack[$shouldBeDictKeyStackPointer] = true;
            } elseif ('}' === $token) {
                if (-1 === $structureStackPointer || -1 === $shouldBeDictKeyStackPointer) {
                    throw new InvalidResourceException($resource);
                }

                $type = self::DICT_END;

                --$structureStackPointer;
                --$shouldBeDictKeyStackPointer;

                if (-1 === $structureStackPointer) {
                    $nextExpectedType = self::END;
                } elseif ('dict' === $structureStack[$structureStackPointer]) {
                    if ($shouldBeDictKey = $shouldBeDictKeyStack[$shouldBeDictKeyStackPointer]) {
                        $nextExpectedType = self::COLUMN;
                    } else {
                        $nextExpectedType = self::DICT_END | self::COMMA;
                    }

                    $shouldBeDictKeyStack[$shouldBeDictKeyStackPointer] = !$shouldBeDictKey;
                } else {
                    $nextExpectedType = self::LIST_END | self::COMMA;
                }
            } elseif ('[' === $token) {
                $type = self::LIST_START;
                $nextExpectedType = self::LIST_END | self::VALUE;

                ++$structureStackPointer;
                $structureStack[$structureStackPointer] = 'list';
            } elseif (']' === $token) {
                $type = self::LIST_END;
                if (-1 === $structureStackPointer) {
                    throw new InvalidResourceException($resource);
                }

                --$structureStackPointer;

                if (-1 === $structureStackPointer) {
                    $nextExpectedType = self::END;
                } elseif ('dict' === $structureStack[$structureStackPointer]) {
                    if ($shouldBeDictKey = $shouldBeDictKeyStack[$shouldBeDictKeyStackPointer]) {
                        $nextExpectedType = self::COLUMN;
                    } else {
                        $nextExpectedType = self::DICT_END | self::COMMA;
                    }

                    $shouldBeDictKeyStack[$shouldBeDictKeyStackPointer] = !$shouldBeDictKey;
                } else {
                    $nextExpectedType = self::LIST_END | self::COMMA;
                }
            } elseif (',' === $token) {
                $type = self::COMMA;

                if (-1 === $structureStackPointer) {
                    throw new InvalidResourceException($resource);
                }

                $nextExpectedType = 'dict' === $structureStack[$structureStackPointer] ? self::KEY : self::VALUE;
            } elseif (':' === $token) {
                $type = self::COLUMN;

                if ('dict' !== ($structureStack[$structureStackPointer] ?? null)) {
                    throw new InvalidResourceException($resource);
                }

                $nextExpectedType = self::VALUE;
            } else {
                if (!isset(self::$validScalarCache[$token])) {
                    try {
                        json_decode($token, associative: true, flags: ($context['json_decode_flags'] ?? 0) | \JSON_THROW_ON_ERROR);

                        self::$validScalarCache[$token] = true;
                    } catch (\JsonException) {
                        self::$validScalarCache[$token] = false;
                    }
                }

                if (!self::$validScalarCache[$token]) {
                    throw new InvalidResourceException($resource);
                }

                if (-1 === $structureStackPointer) {
                    $type = self::VALUE;
                    $nextExpectedType = self::END;
                } elseif ('dict' === $structureStack[$structureStackPointer]) {
                    if ($shouldBeDictKey = $shouldBeDictKeyStack[$shouldBeDictKeyStackPointer]) {
                        if (!isset(self::$validKeyCache[$token])) {
                            self::$validKeyCache[$token] = str_starts_with($token, '"') && str_ends_with($token, '"');
                        }

                        if (!self::$validKeyCache[$token]) {
                            throw new InvalidResourceException($resource);
                        }

                        $type = self::KEY;
                        $nextExpectedType = self::COLUMN;
                    } else {
                        $type = self::VALUE;
                        $nextExpectedType = self::DICT_END | self::COMMA;
                    }

                    $shouldBeDictKeyStack[$shouldBeDictKeyStackPointer] = !$shouldBeDictKey;
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
}
