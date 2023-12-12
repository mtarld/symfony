<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json\Template\Decode;

use Symfony\Component\JsonEncoder\Exception\InvalidResourceException;
use Symfony\Component\JsonEncoder\Stream\StreamReaderInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class Lexer
{
    private const MAX_CHUNK_LENGTH = 8192;

    private const WHITESPACE_CHARS = [' ' => true, "\r" => true, "\t" => true, "\n" => true];
    private const STRUCTURE_CHARS = [',' => true, ':' => true, '{' => true, '}' => true, '[' => true, ']' => true];

    /**
     * @param StreamReaderInterface|resource $stream
     *
     * @return \Traversable<array{0: string, 1: int}>
     *
     * @throws InvalidResourceException
     */
    public function getTokens(mixed $stream, int $offset, ?int $length): \Traversable
    {
        $currentTokenPosition = $offset;
        $token = '';
        $inString = $escaping = false;

        foreach ($this->getChunks($stream, $offset, $length) as $chunk) {
            $chunkLength = \strlen($chunk);

            foreach (str_split($chunk) as $byte) {
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
                        yield [$token, $currentTokenPosition];

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
        }

        if ('' !== $token) {
            yield [$token, $currentTokenPosition];
        }
    }

    /**
     * @param StreamReaderInterface|resource $stream
     *
     * @return \Traversable<string>
     */
    private function getChunks(mixed $stream, int $offset, ?int $length): \Traversable
    {
        $infiniteLength = null === $length;
        $chunkLength = $infiniteLength ? self::MAX_CHUNK_LENGTH : min($length, self::MAX_CHUNK_LENGTH);
        $toReadLength = $length;

        if (\is_resource($stream)) {
            rewind($stream);

            while (!feof($stream) && ($infiniteLength || $toReadLength > 0)) {
                $chunk = stream_get_contents($stream, $infiniteLength ? -1 : min($chunkLength, $toReadLength), $offset);
                $toReadLength -= $l = \strlen($chunk);
                $offset += $l;

                yield $chunk;
            }

            return;
        }

        $stream->seek($offset);

        foreach ($stream as $chunk) {
            if (!$infiniteLength && $toReadLength <= 0) {
                break;
            }

            $chunkLength = \strlen($chunk);

            if ($chunkLength > $toReadLength) {
                yield substr($chunk, 0, $toReadLength);

                break;
            }

            $toReadLength -= $chunkLength;

            yield $chunk;
        }
    }
}
