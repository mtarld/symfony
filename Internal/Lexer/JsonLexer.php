<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Lexer;

use Symfony\Component\Marshaller\Exception\RuntimeException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class JsonLexer implements LexerInterface
{
    public function tokens(mixed $resource, int $offset, int $length, array $context): \Iterator
    {
        $token = '';
        $currentTokenPosition = $offset;

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

                if (\in_array($byte, [',', ':', '{', '}', '[', ']'], true)) {
                    if ('' !== $token) {
                        yield ['position' => $currentTokenPosition, 'value' => $token];

                        $currentTokenPosition += \strlen($token);
                        $token = '';
                    }

                    yield ['position' => $currentTokenPosition, 'value' => $byte];
                    ++$currentTokenPosition;

                    continue;
                }

                if (\in_array($byte, [' ', "\r", "\t", "\n"], true)) {
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
            yield ['position' => $currentTokenPosition, 'value' => $token];
        }
    }
}
