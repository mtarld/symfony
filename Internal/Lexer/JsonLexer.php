<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Lexer;

final class JsonLexer implements LexerInterface
{
    public function tokens(mixed $resource): \Iterator
    {
        return (new \IteratorIterator($this->tokenize($resource)))->getInnerIterator();
    }

    /**
     * @param resource $resource
     *
     * @return \Traversable<string>
     */
    private function tokenize(mixed $resource): \Traversable
    {
        $structureBoundaries = ['{' => true, '}' => true, '[' => true, ']' => true, ':' => true, ',' => true];

        $buffer = '';
        $inString = false;
        $escaping = false;

        while (!feof($resource)) {
            if (false === $line = stream_get_line($resource, 4096, "\n")) {
                yield $buffer;

                return;
            }

            $length = \strlen($line);

            for ($i = 0; $i < $length; ++$i) {
                $byte = $line[$i];

                if ($escaping) {
                    $escaping = false;
                    $buffer .= $byte;

                    continue;
                }

                if ($inString) {
                    $buffer .= $byte;

                    if ('"' === $byte) {
                        $inString = false;
                    } elseif ('\\' === $byte) {
                        $escaping = true;
                    }

                    continue;
                }

                if ('"' === $byte) {
                    $buffer .= $byte;
                    $inString = true;

                    continue;
                }

                if (isset($structureBoundaries[$byte])) {
                    if ('' !== $buffer) {
                        yield $buffer;
                        $buffer = '';
                    }

                    yield $byte;

                    continue;
                }

                // TODO other kind of spaces
                if (' ' === $byte) {
                    continue;
                }

                $buffer .= $byte;
            }
        }

        yield $buffer;
    }
}
