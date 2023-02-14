<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Parser\Json;

use Symfony\Component\Marshaller\Exception\InvalidResourceException;
use Symfony\Component\Marshaller\Internal\Lexer\LexerInterface;
use Symfony\Component\Marshaller\Internal\Parser\ListParserInterface;
use Symfony\Component\Marshaller\Internal\Parser\Parser;
use Symfony\Component\Marshaller\Internal\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class JsonListParser implements ListParserInterface
{
    public function __construct(
        private readonly LexerInterface $lexer,
    ) {
    }

    public function parse(mixed $resource, Type $type, int $offset, int $length, array $context, Parser $parser): \Iterator
    {
        $tokens = $this->tokens($resource, $offset, $length, $context);

        $itemType = $type->collectionValueType();
        $itemOffset = $tokens->current()['position'] + 1;

        $level = 0;

        foreach ($tokens as $token) {
            if (\in_array($token['value'], ['[', '{'], true)) {
                ++$level;

                continue;
            }

            if (\in_array($token['value'], [']', '}'], true)) {
                --$level;

                if (0 === $level) {
                    yield $parser->parse($resource, $itemType, $itemOffset, $token['position'] - $itemOffset, $context);

                    $itemOffset = $tokens->current()['position'] + 1;
                }

                continue;
            }

            if (1 !== $level) {
                continue;
            }

            if (',' === $token['value']) {
                yield $parser->parse($resource, $itemType, $itemOffset, $token['position'] - $itemOffset, $context);

                $itemOffset = $tokens->current()['position'] + 1;
            }
        }
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return \Iterator<array{position: int, value: string}>
     */
    private function tokens(mixed $resource, int $offset, int $length, array $context): \Iterator
    {
        $tokens = $this->lexer->tokens($resource, $offset, $length, $context);

        $level = 0;

        foreach ($tokens as $token) {
            if ('[' === $token['value']) {
                ++$level;

                continue;
            }

            if (']' === $token['value']) {
                --$level;

                if (0 === $level) {
                    $nestedLength = $token['position'] - $offset + 1;
                    $length = -1 === $length ? $nestedLength : \min($length, $nestedLength);

                    return $this->lexer->tokens($resource, $offset, $length, $context);
                }

                continue;
            }
        }

        throw new InvalidResourceException($resource);
    }
}
