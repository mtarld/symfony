<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Parser\Json;

use Symfony\Component\Marshaller\Exception\InvalidResourceException;
use Symfony\Component\Marshaller\Exception\UnexpectedValueException;
use Symfony\Component\Marshaller\Internal\Lexer\LexerInterface;
use Symfony\Component\Marshaller\Internal\Parser\DictParserInterface;
use Symfony\Component\Marshaller\Internal\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class JsonDictParser implements DictParserInterface
{
    private const NESTING_CHARS = ['{' => true, '[' => true];
    private const UNNESTING_CHARS = ['}' => true, ']' => true];
    private const ENDING_CHARS = ['}' => true, 'null' => true];

    public function __construct(
        private readonly LexerInterface $lexer,
    ) {
    }

    public function parse(mixed $resource, Type $type, array $context): ?\Iterator
    {
        $tokens = $this->lexer->tokens($resource, $context['boundary']['offset'], $context['boundary']['length'], $context);

        if ('null' === $tokens->current()['value'] && 1 === iterator_count($tokens)) {
            if (!$type->isNullable()) {
                throw new UnexpectedValueException('TODO');
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
     * @return \Iterator<string, array{offset: int, length: int}>
     */
    public function parseTokens(\Iterator $tokens, mixed $resource, array $context): \Iterator
    {
        $level = 0;
        $offset = $tokens->current()['position'] + 1;
        $key = null;
        $firstValueToken = false;

        foreach ($tokens as $token) {
            if ($firstValueToken) {
                $firstValueToken = false;
                $offset = $token['position'];
            }

            if (isset(self::NESTING_CHARS[$token['value']])) {
                ++$level;

                continue;
            }

            if (isset(self::UNNESTING_CHARS[$token['value']])) {
                --$level;

                if (0 === $level && '}' === $token['value']) {
                    $context['boundary'] = [
                        // TODO boundary DTO
                        'offset' => $offset,
                        'length' => $token['position'] - $offset,
                    ];

                    if (null !== $key && $context['boundary']['length'] > 0) {
                        yield $key => $context['boundary'];
                    }

                    break;
                }

                continue;
            }

            if (1 !== $level) {
                continue;
            }

            if (':' === $token['value']) {
                $firstValueToken = true;

                continue;
            }

            if (',' === $token['value']) {
                $context['boundary'] = [
                    'offset' => $offset,
                    'length' => $token['position'] - $offset,
                ];

                if (null !== $key && $context['boundary']['length'] > 0) {
                    yield $key => $context['boundary'];
                }

                $key = null;

                continue;
            }

            if (null === $key) {
                $key = json_decode($token['value'], flags: $context['json_decode_flags'] ?? 0);
            }
        }

        if (0 !== $level || !isset(self::ENDING_CHARS[$token['value'] ?? null])) {
            throw new InvalidResourceException($resource);
        }
    }
}
