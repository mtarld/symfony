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
use Symfony\Component\Marshaller\Internal\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class JsonListParser implements ListParserInterface
{
    private const NESTING_CHARS = ['{' => true, '[' => true];
    private const UNNESTING_CHARS = ['}' => true, ']' => true];
    private const ENDING_CHARS = [']' => true, 'null' => true];

    public function __construct(
        private readonly LexerInterface $lexer,
    ) {
    }

    public function parse(mixed $resource, Type $type, array $context): ?\Iterator
    {
        $tokens = $this->lexer->tokens($resource, $context['boundary']['offset'], $context['boundary']['length'], $context);

        if ('null' === $tokens->current()['value'] && 1 === iterator_count($tokens)) {
            if (!$type->isNullable()) {
                throw new InvalidResourceException($resource);
            }

            return null;
        }

        return $this->parseTokens($tokens, $resource, $context);
    }

    /**
     * @param \Iterator<array{position: int, value: string}> $tokens
     * @param resource                                       $resource
     * @param array<string, mixed>                           $context
     *
     * @return \Iterator<array{offset: int, length: int}>
     */
    public function parseTokens(\Iterator $tokens, mixed $resource, array $context): \Iterator
    {
        $level = 0;
        $offset = $tokens->current()['position'] + 1;

        foreach ($tokens as $token) {
            if (isset(self::NESTING_CHARS[$token['value']])) {
                ++$level;

                continue;
            }

            if (isset(self::UNNESTING_CHARS[$token['value']])) {
                --$level;

                if (0 === $level) {
                    $context['boundary'] = [
                        'offset' => $offset,
                        'length' => $token['position'] - $offset,
                    ];

                    if ($context['boundary']['length'] > 0) {
                        yield $context['boundary'];
                    }

                    break;
                }

                continue;
            }

            if (1 !== $level) {
                continue;
            }

            if (',' === $token['value']) {
                $context['boundary'] = [
                    'offset' => $offset,
                    'length' => $token['position'] - $offset,
                ];

                if ($context['boundary']['length'] > 0) {
                    yield $context['boundary'];
                }

                $offset = $token['position'] + 1;
            }
        }

        if (0 !== $level || !isset(self::ENDING_CHARS[$token['value'] ?? null])) {
            throw new InvalidResourceException($resource);
        }
    }
}
