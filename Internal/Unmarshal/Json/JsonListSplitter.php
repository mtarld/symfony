<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Unmarshal\Json;

use Symfony\Component\Marshaller\Exception\InvalidResourceException;
use Symfony\Component\Marshaller\Internal\Type\Type;
use Symfony\Component\Marshaller\Internal\Unmarshal\LexerInterface;
use Symfony\Component\Marshaller\Internal\Unmarshal\ListSplitterInterface;

final class JsonListSplitter implements ListSplitterInterface
{
    private const NESTING_CHARS = ['{' => true, '[' => true];
    private const UNNESTING_CHARS = ['}' => true, ']' => true];
    private const ENDING_CHARS = [']' => true, 'null' => true];

    public function __construct(
        private readonly LexerInterface $lexer,
    ) {
    }

    public function split(mixed $resource, Type $type, array $context): ?\Iterator
    {
        $tokens = $this->lexer->tokens($resource, $context['boundary'][0], $context['boundary'][1], $context);
        $currentToken = $tokens->current();

        if ('null' === $currentToken[0] && 1 === iterator_count($tokens)) {
            return null;
        }

        return $this->createBoundaries($tokens, $resource, $currentToken[1] + 1, $context);
    }

    /**
     * @param \Iterator<array{0: string, 1: int}> $tokens
     * @param resource                            $resource
     * @param array<string, mixed>                $context
     *
     * @return \Iterator<array{0: int, 1: int}>
     */
    private function createBoundaries(\Iterator $tokens, mixed $resource, int $offset, array $context): \Iterator
    {
        $level = 0;

        foreach ($tokens as $token) {
            if (isset(self::NESTING_CHARS[$token[0]])) {
                ++$level;

                continue;
            }

            if (isset(self::UNNESTING_CHARS[$token[0]])) {
                --$level;

                if (0 === $level) {
                    if (($length = $token[1] - $offset) > 0) {
                        yield [$offset, $length];
                    }

                    break;
                }

                continue;
            }

            if (1 !== $level) {
                continue;
            }

            if (',' === $token[0]) {
                if (($length = $token[1] - $offset) > 0) {
                    yield [$offset, $length];
                }

                $offset = $token[1] + 1;
            }
        }

        if (0 !== $level || !isset(self::ENDING_CHARS[$token[0] ?? null])) {
            throw new InvalidResourceException($resource);
        }
    }
}
