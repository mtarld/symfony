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
use Symfony\Component\Marshaller\Internal\Unmarshal\DictSplitterInterface;
use Symfony\Component\Marshaller\Internal\Unmarshal\LexerInterface;

final class JsonDictSplitter implements DictSplitterInterface
{
    private const NESTING_CHARS = ['{' => true, '[' => true];
    private const UNNESTING_CHARS = ['}' => true, ']' => true];

    /**
     * @var array<string, string>
     */
    private static array $keysCache = [];

    public function __construct(
        private readonly LexerInterface $lexer,
    ) {
    }

    public function split(mixed $resource, Type $type, array $context): ?\Iterator
    {
        $tokens = $this->lexer->tokens($resource, $context['boundary'][0], $context['boundary'][1], $context);
        $currentToken = $tokens->current();

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
     * @return \Iterator<string, array{0: int, 1: int}>
     */
    public function createBoundaries(\Iterator $tokens, mixed $resource, array $context): \Iterator
    {
        $level = 0;
        $offset = 0;
        $firstValueToken = false;
        $key = null;

        foreach ($tokens as $i => $token) {
            if (0 === $i) {
                continue;
            }

            $value = $token[0];
            $position = $token[1];

            if ($firstValueToken) {
                $firstValueToken = false;
                $offset = $position;
            }

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

            if (':' === $value) {
                $firstValueToken = true;

                continue;
            }

            if (',' === $value) {
                if (null !== $key && ($length = $position - $offset) > 0) {
                    yield $key => [$offset, $length];
                }

                $key = null;

                continue;
            }

            if (null === $key) {
                $key = self::$keysCache[$value] = self::$keysCache[$value] ?? json_decode($value, flags: $context['json_decode_flags'] ?? 0);
            }
        }

        if (-1 !== $level || !isset($key, $value, $position) || '}' !== $value) {
            throw new InvalidResourceException($resource);
        }

        if (($length = $position - $offset) > 0) {
            yield $key => [$offset, $length];
        }
    }
}
