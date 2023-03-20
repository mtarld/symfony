<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Unmarshal\Json;

use Symfony\Component\Marshaller\Exception\InvalidResourceException;
use Symfony\Component\Marshaller\Internal\Type;
use Symfony\Component\Marshaller\Internal\Unmarshal\LexerInterface;
use Symfony\Component\Marshaller\Internal\Unmarshal\ListSplitterInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class JsonListSplitter implements ListSplitterInterface
{
    private const NESTING_CHARS = ['{' => true, '[' => true];
    private const UNNESTING_CHARS = ['}' => true, ']' => true];

    public function __construct(
        private readonly LexerInterface $lexer,
    ) {
    }

    public function split(mixed $resource, Type $type, array $context): ?\Iterator
    {
        $tokens = $this->lexer->tokens($resource, $context['boundary'][0], $context['boundary'][1], $context);

        if ('null' === $tokens->current()[0] && 1 === iterator_count($tokens)) {
            return null;
        }

        return $this->createBoundaries($tokens, $resource, $context);
    }

    /**
     * @param \Iterator<array{0: string, 1: int}> $tokens
     * @param resource                            $resource
     * @param array<string, mixed>                $context
     *
     * @return \Iterator<array{0: int, 1: int}>
     */
    private function createBoundaries(\Iterator $tokens, mixed $resource, array $context): \Iterator
    {
        $level = 0;

        foreach ($tokens as $i => $token) {
            if (0 === $i) {
                continue;
            }

            $value = $token[0];
            $position = $token[1];
            $offset = $offset ?? $position;

            if (isset(self::NESTING_CHARS[$value])) {
                ++$level;

                continue;
            }

            if (isset(self::UNNESTING_CHARS[$value])) {
                --$level;

                continue;
            }

            if (0 !== $level) {
                continue;
            }

            if (',' === $value) {
                if (($length = $position - $offset) > 0) {
                    yield [$offset, $length];
                }

                $offset = null;
            }
        }

        if (-1 !== $level || !isset($value, $offset, $position) || ']' !== $value) {
            throw new InvalidResourceException($resource);
        }

        if (($length = $position - $offset) > 0) {
            yield [$offset, $length];
        }
    }
}
