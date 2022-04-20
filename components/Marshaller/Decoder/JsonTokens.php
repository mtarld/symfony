<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Decoder;

final class JsonTokens implements \IteratorAggregate
{
    /**
     * @param iterable<string> $chunks
     */
    public function __construct(private iterable $chunks)
    {
    }

    public function getIterator(): \Generator
    {
        $charBuffer = '';

        $specialChars = $this->getSpecialChars();
        $delimiterChars = $this->getDelimiterChars();

        $escaping = false;
        $inString = false;

        foreach ($this->chunks as $chunk) {
            $length = mb_strlen($chunk);

            for ($offset = 0; $offset < $length; ++$offset) {
                $char = $chunk[$offset];

                // Reset from eventual escaping
                if ($escaping) {
                    $escaping = false;
                    $charBuffer .= $char;

                    continue;
                }

                // Opening string
                if (!$inString && '"' === $char) {
                    $inString = true;
                    $charBuffer .= $char;

                    continue;
                }

                // Closing string
                if ($inString && '"' === $char && !$escaping) {
                    $inString = false;
                    $charBuffer .= $char;

                    continue;
                }

                // Escaping
                if ($inString && '\\' === $char) {
                    $escaping = true;
                    $charBuffer .= $char;

                    continue;
                }

                // Yielding delimiters individually
                if (isset($delimiterChars[$char])) {
                    yield $char;

                    continue;
                }

                if (isset($specialChars[$char])) {
                    yield $charBuffer;
                    $charBuffer = '';

                    continue;
                }

                $charBuffer .= $char;
            }
        }
    }

    private function getSpecialChars(): array
    {
        return array_merge([
            '\\' => true,
            '"' => true,
            ' ' => true,
            "\n" => true,
            "\r" => true,
            "\t" => true,
        ], $this->getDelimiterChars());
    }

    private function getDelimiterChars(): array
    {
        return [
            '{' => true,
            '}' => true,
            '[' => true,
            ']' => true,
            ':' => true,
            ',' => true,
        ];
    }
}
