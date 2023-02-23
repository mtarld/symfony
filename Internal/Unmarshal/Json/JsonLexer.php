<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Unmarshal\Json;

use Symfony\Component\Marshaller\Exception\RuntimeException;
use Symfony\Component\Marshaller\Internal\Unmarshal\Boundary;
use Symfony\Component\Marshaller\Internal\Unmarshal\LexerInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class JsonLexer implements LexerInterface
{
    private const WHITESPACE_CHARS = [' ' => true, "\r" => true, "\t" => true, "\n" => true];
    private const STRUCTURE_CHARS = [',' => true, ':' => true, '{' => true, '}' => true, '[' => true, ']' => true];

    public function tokens(mixed $resource, Boundary $boundary, array $context): \Iterator
    {
        $offset = $currentTokenPosition = $boundary->offset;
        $length = $boundary->length;

        $token = '';

        $inString = false;
        $escaping = false;

        $chunkLength = -1 === $length ? 4096 : min($length, 4096);
        $readLength = 0;

        // TODO validate JSON

        rewind($resource);

        while (!feof($resource) && (-1 === $length || $readLength < $length)) {
            if (false === $buffer = stream_get_contents($resource, $chunkLength, $offset)) {
                throw new RuntimeException('Cannot read JSON resource.');
            }

            $bufferLength = \strlen($buffer);
            $readLength += $bufferLength;

            for ($i = 0; $i < $bufferLength; ++$i) {
                $byte = $buffer[$i];

                if ($escaping) {
                    $escaping = false;
                    $token .= $byte;

                    continue;
                }

                if ($inString) {
                    $token .= $byte;

                    if ('"' === $byte) {
                        $inString = false;
                    } elseif ('\\' === $byte) {
                        $escaping = true;
                    }

                    continue;
                }

                if ('"' === $byte) {
                    $token .= $byte;
                    $inString = true;

                    continue;
                }

                // TODO in_array instead?
                if (isset(self::STRUCTURE_CHARS[$byte])) {
                    if ('' !== $token) {
                        // TODO DTO instead
                        yield [$token, $currentTokenPosition];

                        $currentTokenPosition += \strlen($token);
                        $token = '';
                    }

                    yield [$byte, $currentTokenPosition];

                    ++$currentTokenPosition;

                    continue;
                }

                // TODO in_array instead?
                if (isset(self::WHITESPACE_CHARS[$byte])) {
                    if ('' === $token) {
                        ++$currentTokenPosition;
                    }
                } else {
                    $token .= $byte;
                }
            }

            $offset += $bufferLength;
        }

        if (!$inString && !$escaping && '' !== $token) {
            yield [$token, $currentTokenPosition];
        }
    }
}
