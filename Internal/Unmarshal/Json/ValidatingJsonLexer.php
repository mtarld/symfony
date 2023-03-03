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

        $structureStack = $shouldBeDictKeyStack = [];
        $structurePointer = $shouldBeDictKeyPointer = -1;

        foreach ($this->lexer->tokens($resource, $offset, $length, $context) as $i => [$token, $offset]) {
            if ('{' === $token) {
                if (!(self::DICT_START & $expectedType)) {
                    throw new InvalidResourceException($resource);
                }

                $structureStack[++$structurePointer] = 'dict';
                $shouldBeDictKeyStack[++$shouldBeDictKeyPointer] = true;

                $expectedType = self::DICT_END | self::KEY;

                yield [$token, $offset];

                continue;
            }

            if ('}' === $token) {
                if (!(self::DICT_END & $expectedType) || -1 === $structurePointer || -1 === $shouldBeDictKeyPointer) {
                    throw new InvalidResourceException($resource);
                }

                --$structurePointer;
                --$shouldBeDictKeyPointer;

                if (-1 === $structurePointer) {
                    $expectedType = self::END;
                } elseif ('dict' === $structureStack[$structurePointer]) {
                    $expectedType = ($shouldBeDictKey = $shouldBeDictKeyStack[$shouldBeDictKeyPointer]) ? self::COLUMN : self::DICT_END | self::COMMA;
                    $shouldBeDictKeyStack[$shouldBeDictKeyPointer] = !$shouldBeDictKey;
                } else {
                    $expectedType = self::LIST_END | self::COMMA;
                }

                yield [$token, $offset];

                continue;
            }

            if ('[' === $token) {
                if (!(self::LIST_START & $expectedType)) {
                    throw new InvalidResourceException($resource);
                }

                $expectedType = self::LIST_END | self::VALUE;

                $structureStack[++$structurePointer] = 'list';

                yield [$token, $offset];

                continue;
            }

            if (']' === $token) {
                if (!(self::LIST_END & $expectedType) || -1 === $structurePointer) {
                    throw new InvalidResourceException($resource);
                }

                --$structurePointer;

                if (-1 === $structurePointer) {
                    $expectedType = self::END;
                } elseif ('dict' === $structureStack[$structurePointer]) {
                    $expectedType = ($shouldBeDictKey = $shouldBeDictKeyStack[$shouldBeDictKeyPointer]) ? self::COLUMN : self::DICT_END | self::COMMA;
                    $shouldBeDictKeyStack[$shouldBeDictKeyPointer] = !$shouldBeDictKey;
                } else {
                    $expectedType = self::LIST_END | self::COMMA;
                }

                yield [$token, $offset];

                continue;
            }

            if (',' === $token) {
                if (!(self::COMMA & $expectedType) || -1 === $structurePointer) {
                    throw new InvalidResourceException($resource);
                }

                $expectedType = 'dict' === $structureStack[$structurePointer] ? self::KEY : self::VALUE;

                yield [$token, $offset];

                continue;
            }

            if (':' === $token) {
                if (!(self::COLUMN & $expectedType) || 'dict' !== ($structureStack[$structurePointer] ?? null)) {
                    throw new InvalidResourceException($resource);
                }

                $expectedType = self::VALUE;

                yield [$token, $offset];

                continue;
            }

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

            if (-1 === $structurePointer) {
                if (!(self::VALUE & $expectedType)) {
                    throw new InvalidResourceException($resource);
                }

                $expectedType = self::END;

                yield [$token, $offset];

                continue;
            }

            if ('dict' === $structureStack[$structurePointer]) {
                if ($shouldBeDictKey = $shouldBeDictKeyStack[$shouldBeDictKeyPointer]) {
                    if (!(self::KEY & $expectedType)) {
                        throw new InvalidResourceException($resource);
                    }

                    if (!isset(self::$validKeyCache[$token])) {
                        self::$validKeyCache[$token] = str_starts_with($token, '"') && str_ends_with($token, '"');
                    }

                    if (!self::$validKeyCache[$token]) {
                        throw new InvalidResourceException($resource);
                    }

                    $expectedType = self::COLUMN;
                } else {
                    if (!(self::VALUE & $expectedType)) {
                        throw new InvalidResourceException($resource);
                    }

                    $expectedType = self::DICT_END | self::COMMA;
                }

                $shouldBeDictKeyStack[$shouldBeDictKeyPointer] = !$shouldBeDictKey;

                yield [$token, $offset];

                continue;
            }

            if (!(self::VALUE & $expectedType)) {
                throw new InvalidResourceException($resource);
            }

            $expectedType = self::LIST_END | self::COMMA;

            yield [$token, $offset];
        }

        if (self::END !== $expectedType) {
            throw new InvalidResourceException($resource);
        }
    }
}
