<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Context\Encoder;

use Symfony\Component\Serializer\Exception\InvalidArgumentException;

final class CsvEncoderOptions
{
    /**
     * Column delimiter character.
     * Must be one character only.
     */
    private ?string $delimiter = null;

    /**
     * Field enclosure character.
     * This must be one character only.
     */
    private ?string $enclosure = null;

    /**
     * Escape character.
     * Must be one character only.
     */
    private ?string $escapeChar = null;

    /**
     * Whether formulas should be escaped.
     */
    private ?string $endOfLine = null;

    /**
     * Key separator when (un)flattening arrays.
     */
    private ?string $keySeparator = null;

    /**
     * CSV table headers.
     *
     * @var list<string>|null $headers
     */
    private ?array $headers = null;

    /**
     * Whether the decoded result should be considered as a collection
     * or as a single element.
     */
    private ?bool $asCollection = null;

    /**
     * Whether formulas should be escaped.
     */
    private ?bool $escapeFormulas = null;

    /**
     * Whether the input (or output) isn't containing (or won't contain) headers.
     */
    private ?bool $withoutHeaders = null;

    /**
     * End of line characters.
     */
    private ?bool $outputUtf8Bom = null;

    public function getDelimiter(): string
    {
        return $this->delimiter ?? ',';
    }

    public function setDelimiter(?string $delimiter): self
    {
        if (null !== $delimiter && \strlen($delimiter) > 1) {
            throw new InvalidArgumentException(sprintf('The "%s" delimiter is not valid. It must be one character only.', $delimiter));
        }

        $this->delimiter = $delimiter;

        return $this;
    }

    public function getEnclosure(): string
    {
        return $this->enclosure ?? '"';
    }

    public function setEnclosure(?string $enclosure): self
    {
        if (null !== $enclosure && \strlen($enclosure) > 1) {
            throw new InvalidArgumentException(sprintf('The "%s" enclosure is not valid. It must be one character only.', $enclosure));
        }

        $this->enclosure = $enclosure;

        return $this;
    }

    public function getEscapeChar(): string
    {
        return $this->escapeChar ?? '';
    }

    public function setEscapeChar(?string $escapeChar): self
    {
        if (null !== $escapeChar && \strlen($escapeChar) > 1) {
            throw new InvalidArgumentException(sprintf('The "%s" escape character is not valid. It must be one character only.', $escapeChar));
        }

        $this->escapeChar = $escapeChar;

        return $this;
    }

    public function getEndOfLine(): string
    {
        return $this->endOfLine ?? "\n";
    }

    public function setEndOfLine(?string $endOfLine): self
    {
        $this->endOfLine = $endOfLine;

        return $this;
    }

    public function getKeySeparator(): string
    {
        return $this->keySeparator ?? '.';
    }

    public function setKeySeparator(?string $keySeparator): self
    {
        $this->keySeparator = $keySeparator;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getHeaders(): array
    {
        return $this->headers ?? [];
    }

    /**
     * @param list<string>|null $headers
     */
    public function setHeaders(?array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function isAsCollection(): bool
    {
        return $this->asCollection ?? true;
    }

    public function setAsCollection(?bool $asCollection): self
    {
        $this->asCollection = $asCollection;

        return $this;
    }

    public function isEscapeFormulas(): bool
    {
        return $this->escapeFormulas ?? false;
    }

    public function setEscapeFormulas(?bool $escapeFormulas): self
    {
        $this->escapeFormulas = $escapeFormulas;

        return $this;
    }

    public function isWithoutHeaders(): bool
    {
        return $this->withoutHeaders ?? false;
    }

    public function setWithoutHeaders(?bool $withoutHeaders): self
    {
        $this->withoutHeaders = $withoutHeaders;

        return $this;
    }

    public function isOutputUtf8Bom(): bool
    {
        return $this->outputUtf8Bom ?? false;
    }

    public function setOutputUtf8Bom(?bool $outputUtf8Bom): self
    {
        $this->outputUtf8Bom = $outputUtf8Bom;

        return $this;
    }

    public function merge(self $other): self
    {
        $this->delimiter ??= $other->delimiter;
        $this->enclosure ??= $other->enclosure;
        $this->escapeChar ??= $other->escapeChar;
        $this->endOfLine ??= $other->endOfLine;
        $this->keySeparator ??= $other->keySeparator;
        $this->headers ??= $other->headers;
        $this->escapeFormulas ??= $other->escapeFormulas;
        $this->withoutHeaders ??= $other->withoutHeaders;
        $this->outputUtf8Bom ??= $other->outputUtf8Bom;

        return $this;
    }

    /**
     * @internal
     *
     * @param array<string, mixed> $legacyContext
     */
    public static function fromLegacyContext(array $legacyContext = []): self
    {
        return (new self())
            ->setDelimiter($legacyContext['csv_delimiter'] ?? null)
            ->setEnclosure($legacyContext['csv_enclosure'] ?? null)
            ->setEscapeChar($legacyContext['csv_escape_char'] ?? null)
            ->setKeySeparator($legacyContext['csv_key_separator'] ?? null)
            ->setHeaders($legacyContext['csv_headers'] ?? null)
            ->setEscapeFormulas($legacyContext['csv_escape_formulas'] ?? null)
            ->setAsCollection($legacyContext['as_collection'] ?? null)
            ->setWithoutHeaders($legacyContext['no_headers'] ?? null)
            ->setEndOfLine($legacyContext['csv_end_of_line'] ?? null)
            ->setOutputUtf8Bom($legacyContext['output_utf8_bom'] ?? null)
        ;
    }
}
