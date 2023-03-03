<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Unmarshal\Json;

use Symfony\Component\Marshaller\Exception\RuntimeException;
use Symfony\Component\Marshaller\Internal\Unmarshal\LexerInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class JsonLexer implements LexerInterface
{
    private const MAX_CHUNK_LENGTH = 8192;

    private const WHITESPACE_CHARS = [' ' => true, "\r" => true, "\t" => true, "\n" => true];
    private const STRUCTURE_CHARS = [',' => true, ':' => true, '{' => true, '}' => true, '[' => true, ']' => true];
    private const EMPTY_TOKENS = ['' => true, "\xEF\xBB\xBF" => true];

    public function tokens(mixed $resource, int $offset, int $length, array $context): \Iterator
    {
        $currentTokenPosition = $offset;

        $token = '';

        $inString = $escaping = false;
        $infiniteLength = -1 === $length;
        $chunkLength = $infiniteLength ? self::MAX_CHUNK_LENGTH : min($length, self::MAX_CHUNK_LENGTH);

        rewind($resource);

        // TODO validate opt in

        $toReadLength = $length;

        while (!feof($resource) && ($infiniteLength || $toReadLength > 0)) {
            try {
                if (false === $buffer = stream_get_contents($resource, $infiniteLength ? -1 : min($chunkLength, $toReadLength), $offset)) {
                    throw new \RuntimeException();
                }
            } catch (\Throwable) {
                throw new RuntimeException('Cannot read JSON resource.');
            }

            $toReadLength -= $bufferLength = \strlen($buffer);

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

                if (isset(self::STRUCTURE_CHARS[$byte]) || isset(self::WHITESPACE_CHARS[$byte])) {
                    if ('' !== $token) {
                        if (!isset(self::EMPTY_TOKENS[$token])) {
                            yield [$token, $currentTokenPosition];
                        }

                        $currentTokenPosition += \strlen($token);
                        $token = '';
                    }

                    if (!isset(self::WHITESPACE_CHARS[$byte])) {
                        yield [$byte, $currentTokenPosition];
                    }

                    if ('' !== $byte) {
                        ++$currentTokenPosition;
                    }

                    continue;
                }

                $token .= $byte;
            }

            $offset += $bufferLength;
        }

        if (!isset(self::EMPTY_TOKENS[$token])) {
            yield [$token, $currentTokenPosition];
        }
    }
}
