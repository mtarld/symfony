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
    private const NESTING_CHARS = ['{' => true, '[' => true];
    private const UNNESTING_CHARS = ['}' => true, ']' => true];

    public function __construct(
        private readonly LexerInterface $lexer,
    ) {
    }

    public function parse(mixed $resource, Type $type, array $context, Parser $parser): ?\Iterator
    {
        $tokens = $this->lexer->tokens($resource, $context['resource']['offset'], $context['resource']['length'], $context);

        if ('null' === $tokens->current()['value'] && 1 === \iterator_count($tokens)) {
            if (!$type->isNullable()) {
                throw new InvalidResourceException($resource);
            }

            return null;
        }

        return $this->parseTokens($tokens, $resource, $type->collectionValueType(), $context, $parser);
    }

    /**
     * @param \Iterator<array{position: int, value: string}> $tokens
     * @param resource                                       $resource
     * @param array<string, mixed>                           $context
     *
     * @return \Iterator<mixed>
     */
    public function parseTokens(\Iterator $tokens, mixed $resource, Type $valueType, array $context, Parser $parser): \Iterator
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
                    $context['resource'] = [
                        'offset' => $offset,
                        'length' => $token['position'] - $offset,
                    ];

                    // TODO same in dict
                    if ($context['resource']['length'] > 0) {
                        yield $parser->parse($resource, $valueType, $context);
                    }

                    break;
                }

                continue;
            }

            if (1 !== $level) {
                continue;
            }

            if (',' === $token['value']) {
                $context['resource'] = [
                    'offset' => $offset,
                    'length' => $token['position'] - $offset,
                ];

                yield $parser->parse($resource, $valueType, $context);

                $offset = $token['position'] + 1;
            }
        }

        if (0 !== $level || !\in_array($token['value'], ['null', ']'], true)) {
            throw new InvalidResourceException($resource);
        }
    }
}
