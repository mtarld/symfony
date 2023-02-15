<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Parser\Json;

use Symfony\Component\Marshaller\Exception\InvalidResourceException;
use Symfony\Component\Marshaller\Exception\LogicException;
use Symfony\Component\Marshaller\Internal\Lexer\LexerInterface;
use Symfony\Component\Marshaller\Internal\Parser\ScalarParserInterface;
use Symfony\Component\Marshaller\Internal\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class JsonScalarParser implements ScalarParserInterface
{
    public function __construct(
        private readonly LexerInterface $lexer,
    ) {
    }

    public function parse(mixed $resource, Type $type, array $context): int|float|string|bool|null
    {
        $tokens = $this->lexer->tokens($resource, $context['boundary']['offset'], $context['boundary']['length'], $context);

        $result = json_decode($tokens->current()['value'], flags: $context['json_decode_flags'] ?? 0);
        if (null === $result) {
            if (!$type->isNullable()) {
                throw new InvalidResourceException($resource);
            }

            return null;
        }

        return match ($type->name()) {
            'int' => (int) $result,
            'float' => (float) $result,
            'string' => (string) $result,
            'bool' => (bool) $result,
            default => throw new LogicException(sprintf('Cannot cast scalar to "%s".', $type->name())),
        };
    }
}
