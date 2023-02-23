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
use Symfony\Component\Marshaller\Internal\Unmarshal\Boundary;
use Symfony\Component\Marshaller\Internal\Unmarshal\LexerInterface;
use Symfony\Component\Marshaller\Internal\Unmarshal\ListSplitterInterface;
use Symfony\Component\Marshaller\Internal\Unmarshal\Token;

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
        $tokens = $this->lexer->tokens($resource, $context['boundary'], $context);

        if ('null' === $tokens->current()[0] && 1 === iterator_count($tokens)) {
            return null;
        }

        return $this->createBoundaries($tokens, $resource, $context);
    }

    /**
     * @param \Iterator<Token> $tokens
     * @param resource                                       $resource
     * @param array<string, mixed>                           $context
     *
     * @return \Iterator<Boundary>
     */
    private function createBoundaries(\Iterator $tokens, mixed $resource, array $context): \Iterator
    {
        $level = 0;
        $offset = $tokens->current()[1] + 1;

        foreach ($tokens as $token) {
            // TODO in_array instead?
            if (isset(self::NESTING_CHARS[$token[0]])) {
                ++$level;

                continue;
            }

            // TODO in_array instead?
            if (isset(self::UNNESTING_CHARS[$token[0]])) {
                --$level;

                if (0 === $level) {
                    $boundary = new Boundary($offset, $token[1] - $offset);

                    if ($boundary->length > 0) {
                        yield $boundary;
                    }

                    break;
                }

                continue;
            }

            if (1 !== $level) {
                continue;
            }

            if (',' === $token[0]) {
                $boundary = new Boundary($offset, $token[1] - $offset);

                if ($boundary->length > 0) {
                    yield $boundary;
                }

                $offset = $token[1] + 1;
            }
        }

        if (0 !== $level || !isset(self::ENDING_CHARS[$token[0] ?? null])) {
            throw new InvalidResourceException($resource);
        }
    }
}
