<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Parser;

use Symfony\Component\Marshaller\Exception\InvalidResourceException;
use Symfony\Component\Marshaller\Exception\LogicException;
use Symfony\Component\Marshaller\Exception\UnsupportedTypeException;
use Symfony\Component\Marshaller\Internal\Lexer\LexerInterface;
use Symfony\Component\Marshaller\Internal\Type\Type;
use Symfony\Component\Marshaller\Internal\Type\UnionType;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class Parser
{
    public function __construct(
        private readonly LexerInterface $lexer,
        private readonly ListParserInterface $listParser,
    ) {
    }

    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     */
    public function parse(mixed $resource, Type|UnionType $type, int $offset, int $length, array $context): mixed
    {
        if ($type->isScalar()) {
            $tokens = $this->lexer->tokens($resource, $offset, $length, $context);

            return json_decode($tokens->current()['value'], flags: $context['json_decode_flags'] ?? 0);
        }

        if ($type->isDict()) {
            $tokens = $this->getDictTokens($resource, $offset, $length, $context);

            return iterator_to_array($this->parseDict($resource, $tokens, $type, $context));
            return $this->parseDict($resource, $tokens, $type, $context);
        }

        if ($type->isList()) {
            $tokens = $this->getListTokens($resource, $offset, $length, $context);

            return iterator_to_array($this->parseList($resource, $tokens, $type, $context));
            return $this->parseList($resource, $tokens, $type, $context);
        }

        throw new UnsupportedTypeException($type);
    }

    private function getDictTokens(mixed $resource, int $offset, int $length, array $context): \Iterator
    {
        $tokens = $this->lexer->tokens($resource, $offset, $length, $context);
        $level = 0;

        while ($tokens->valid()) {
            $token = $tokens->current();
            $tokens->next();

            if ('{' === $token['value']) {
                ++$level;

                continue;
            }

            if ('}' === $token['value']) {
                --$level;

                if (0 === $level) {
                    $dictLength = $token['position'] - $offset + 1;
                    $length = -1 === $length ? $dictLength : \min($length, $dictLength);

                    return $this->lexer->tokens($resource, $offset, $length, $context);
                }

                continue;
            }
        }

        throw new InvalidResourceException($resource);
    }

    private function getListTokens(mixed $resource, int $offset, int $length, array $context): \Iterator
    {
        $tokens = $this->lexer->tokens($resource, $offset, $length, $context);
        $level = 0;

        while ($tokens->valid()) {
            $token = $tokens->current();
            $tokens->next();

            if ('[' === $token['value']) {
                ++$level;

                continue;
            }

            if (']' === $token['value']) {
                --$level;

                if (0 === $level) {
                    $listLength = $token['position'] - $offset + 1;
                    $length = -1 === $length ? $listLength : \min($length, $listLength);

                    return $this->lexer->tokens($resource, $offset, $length, $context);
                }

                continue;
            }
        }

        throw new InvalidResourceException($resource);
    }

    private function parseDict(mixed $resource, \Iterator $tokens, Type $type, array $context): \Iterator
    {
        $level = 0;

        $key = null;

        $valueType = $type->collectionValueType();
        $valueOffset = null;

        foreach ($tokens as $token) {
            if (\in_array($token['value'], ['[', '{'], true)) {
                ++$level;

                continue;
            }

            if (\in_array($token['value'], [']', '}'], true)) {
                --$level;

                if (0 === $level) {
                    yield $key => $this->parse($resource, $valueType, $valueOffset, $token['position'] - $valueOffset, $context);

                    $key = null;
                }

                continue;
            }

            if (1 !== $level) {
                continue;
            }

            if (':' === $token['value']) {
                continue;
            }

            if (',' === $token['value']) {
                yield $key => $this->parse($resource, $valueType, $valueOffset, $token['position'] - $valueOffset, $context);

                $key = null;

                continue;
            }

            if (null === $key) {
                $key = json_decode($token['value'], flags: $context['json_decode_flags'] ?? 0);

                continue;
            }

            $valueOffset = $token['position'];
        }
    }

    private function parseList(mixed $resource, \Iterator $tokens, Type $type, array $context): \Generator
    {
        $level = 0;

        $itemType = $type->collectionValueType();
        $itemOffset = $tokens->current()['position'] + 1;

        foreach ($tokens as $token) {
            if (\in_array($token['value'], ['[', '{'], true)) {
                ++$level;

                continue;
            }

            if (\in_array($token['value'], [']', '}'], true)) {
                --$level;

                if (0 === $level) {
                    yield $this->parse($resource, $itemType, $itemOffset, $token['position'] - $itemOffset, $context);

                    $itemOffset = $tokens->current()['position'] + 1;
                }

                continue;
            }

            if (1 !== $level) {
                continue;
            }

            if (',' === $token['value']) {
                yield $this->parse($resource, $itemType, $itemOffset, $token['position'] - $itemOffset, $context);

                $itemOffset = $tokens->current()['position'] + 1;
            }
        }
    }
}

