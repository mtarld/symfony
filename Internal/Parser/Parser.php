<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Parser;

use Symfony\Component\Marshaller\Exception\UnsupportedTypeException;
use Symfony\Component\Marshaller\Internal\Lexer\JsonLexer;
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
        private readonly ScalarParserInterface $scalarParser,
        private readonly ListParserInterface $listParser,
        private readonly DictParserInterface $dictParser,
    ) {
    }

    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     */
    public function parse(mixed $resource, Type|UnionType $type, int $offset, int $length, array $context): mixed
    {
        if ($type->isNullable()) {
            $l = new JsonLexer();

            $tokens = $l->tokens($resource, $offset, $length, $context);
            if ('null' === $tokens->current()['value']) {
                return null;
            }
        }

        if ($type->isScalar()) {
            return $this->scalarParser->parse($resource, $type, $offset, $length, $context);
        }

        if ($type->isDict()) {
            $result = $this->dictParser->parse($resource, $type, $offset, $length, $context, $this);

            return $type->isIterable() ? $result : iterator_to_array($result);
        }

        if ($type->isList()) {
            $result = $this->listParser->parse($resource, $type, $offset, $length, $context, $this);

            return $type->isIterable() ? $result : iterator_to_array($result);
        }

        throw new UnsupportedTypeException($type);
    }
}

