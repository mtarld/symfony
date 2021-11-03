<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Encoder;

use Symfony\Component\Serializer\Context\Context;
use Symfony\Component\Serializer\Context\Encoder\CsvEncoderOptions;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Encodes CSV data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Oliver Hoff <oliver@hofff.com>
 */
class CsvEncoder implements EncoderInterface, DecoderInterface
{
    public const FORMAT = 'csv';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const DELIMITER_KEY = 'csv_delimiter';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const ENCLOSURE_KEY = 'csv_enclosure';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const ESCAPE_CHAR_KEY = 'csv_escape_char';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const KEY_SEPARATOR_KEY = 'csv_key_separator';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const HEADERS_KEY = 'csv_headers';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const ESCAPE_FORMULAS_KEY = 'csv_escape_formulas';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const AS_COLLECTION_KEY = 'as_collection';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const NO_HEADERS_KEY = 'no_headers';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const END_OF_LINE = 'csv_end_of_line';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const OUTPUT_UTF8_BOM_KEY = 'output_utf8_bom';

    private const UTF8_BOM = "\xEF\xBB\xBF";

    private array $formulasStartCharacters = ['=', '-', '+', '@'];

    private ?CsvEncoderOptions $defaultOptions = null;

    private ?array $defaultLegacyContext = null;

    /**
     * @param Context|null $defaultContext
     */
    public function __construct(/* Context $defaultContext = null */)
    {
        /** @var Context|array|null $defaultContext */
        $defaultContext = 0 < \func_num_args() ? \func_get_arg(0) : null;

        if (\is_array($defaultContext)) {
            trigger_deprecation('symfony/serializer', '6.1', 'Passing an array for $defaultContext is deprecated.');
            $this->defaultLegacyContext = array_merge((new CsvEncoderOptions())->toLegacyContext(), $defaultContext);

            return;
        }

        $this->defaultOptions = $defaultContext?->getOptions(CsvEncoderOptions::class) ?? new CsvEncoderOptions();
    }

    /**
     * {@inheritdoc}
     *
     * @param Context|null $context
     */
    public function encode(mixed $data, string $format /*, Context $context = null */): string
    {
        $context = $this->getContext(2 < \func_num_args() ? \func_get_arg(2) : null);

        $handle = fopen('php://temp,', 'w+');

        if (!is_iterable($data)) {
            $data = [[$data]];
        } elseif (empty($data)) {
            $data = [[]];
        } else {
            // Sequential arrays of arrays are considered as collections
            $i = 0;
            foreach ($data as $key => $value) {
                if ($i !== $key || !\is_array($value)) {
                    $data = [$data];
                    break;
                }

                ++$i;
            }
        }

        foreach ($data as &$value) {
            $flattened = [];
            $this->flatten($value, $flattened, $context['csv_key_separator'], '', $context['csv_escape_formulas']);
            $value = $flattened;
        }
        unset($value);

        $headers = array_merge(array_values($context['csv_headers']), array_diff($this->extractHeaders($data), $context['csv_headers']));

        if (!$context['no_headers']) {
            fputcsv($handle, $headers, $context['csv_delimiter'], $context['csv_enclosure'], $context['csv_escape_char']);
            if ("\n" !== $context['csv_end_of_line'] && 0 === fseek($handle, -1, \SEEK_CUR)) {
                fwrite($handle, $context['csv_end_of_line']);
            }
        }

        $headers = array_fill_keys($headers, '');
        foreach ($data as $row) {
            fputcsv($handle, array_replace($headers, $row), $context['csv_delimiter'], $context['csv_enclosure'], $context['csv_escape_char']);
            if ("\n" !== $context['csv_end_of_line'] && 0 === fseek($handle, -1, \SEEK_CUR)) {
                fwrite($handle, $context['csv_end_of_line']);
            }
        }

        rewind($handle);
        $value = stream_get_contents($handle);
        fclose($handle);

        if ($context['output_utf8_bom']) {
            if (!preg_match('//u', $value)) {
                throw new UnexpectedValueException('You are trying to add a UTF-8 BOM to a non UTF-8 text.');
            }

            $value = self::UTF8_BOM.$value;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEncoding(string $format): bool
    {
        return self::FORMAT === $format;
    }

    /**
     * {@inheritdoc}
     *
     * @param Context|null $context
     */
    public function decode(string $data, string $format /*, Context $context = null */): mixed
    {
        // TODO enforce deprecation in debugclassloader (L614)

        $context = $this->getContext(2 < \func_num_args() ? \func_get_arg(2) : null);

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $data);
        rewind($handle);

        if (str_starts_with($data, self::UTF8_BOM)) {
            fseek($handle, \strlen(self::UTF8_BOM));
        }

        $headers = null;
        $nbHeaders = 0;
        $headerCount = [];
        $result = [];

        while (false !== ($cols = fgetcsv($handle, 0, $context['csv_delimiter'], $context['csv_enclosure'], $context['csv_escape_char']))) {
            $nbCols = \count($cols);

            if (null === $headers) {
                $nbHeaders = $nbCols;

                if ($context['no_headers']) {
                    for ($i = 0; $i < $nbCols; ++$i) {
                        $headers[] = [$i];
                    }
                    $headerCount = array_fill(0, $nbCols, 1);
                } else {
                    foreach ($cols as $col) {
                        $header = explode($context['csv_key_separator'], $col);
                        $headers[] = $header;
                        $headerCount[] = \count($header);
                    }

                    continue;
                }
            }

            $item = [];
            for ($i = 0; ($i < $nbCols) && ($i < $nbHeaders); ++$i) {
                $depth = $headerCount[$i];
                $arr = &$item;
                for ($j = 0; $j < $depth; ++$j) {
                    // Handle nested arrays
                    if ($j === ($depth - 1)) {
                        $arr[$headers[$i][$j]] = $cols[$i];

                        continue;
                    }

                    if (!isset($arr[$headers[$i][$j]])) {
                        $arr[$headers[$i][$j]] = [];
                    }

                    $arr = &$arr[$headers[$i][$j]];
                }
            }

            $result[] = $item;
        }
        fclose($handle);

        if ($context['as_collection']) {
            return $result;
        }

        if (empty($result) || isset($result[1])) {
            return $result;
        }

        // If there is only one data line in the document, return it (the line), the result is not considered as a collection
        return $result[0];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDecoding(string $format): bool
    {
        return self::FORMAT === $format;
    }

    /**
     * Flattens an array and generates keys including the path.
     */
    private function flatten(iterable $array, array &$result, string $keySeparator, string $parentKey = '', bool $escapeFormulas = false)
    {
        foreach ($array as $key => $value) {
            if (is_iterable($value)) {
                $this->flatten($value, $result, $keySeparator, $parentKey.$key.$keySeparator, $escapeFormulas);
            } else {
                if ($escapeFormulas && \in_array(substr((string) $value, 0, 1), $this->formulasStartCharacters, true)) {
                    $result[$parentKey.$key] = "\t".$value;
                } else {
                    // Ensures an actual value is used when dealing with true and false
                    $result[$parentKey.$key] = false === $value ? 0 : (true === $value ? 1 : $value);
                }
            }
        }
    }

    /**
     * @return string[]
     */
    private function extractHeaders(iterable $data): array
    {
        $headers = [];
        $flippedHeaders = [];

        foreach ($data as $row) {
            $previousHeader = null;

            foreach ($row as $header => $_) {
                if (isset($flippedHeaders[$header])) {
                    $previousHeader = $header;
                    continue;
                }

                if (null === $previousHeader) {
                    $n = \count($headers);
                } else {
                    $n = $flippedHeaders[$previousHeader] + 1;

                    for ($j = \count($headers); $j > $n; --$j) {
                        ++$flippedHeaders[$headers[$j] = $headers[$j - 1]];
                    }
                }

                $headers[$n] = $header;
                $flippedHeaders[$header] = $n;
                $previousHeader = $header;
            }
        }

        return $headers;
    }

    private function getOptions(?Context $context): CsvEncoderOptions
    {
        $options = $context?->getOptions(CsvEncoderOptions::class);

        return null !== $options ? $options->merge($this->defaultOptions) : $this->defaultOptions;
    }

    /**
     * Prepare a context array filled with defaults based
     * on either a Context object or the legacy context array.
     *
     * Used for BC layer.
     *
     * @param Context|array<string, mixed>|null $context
     *
     * @return array<string, mixed>
     */
    private function getContext(Context|array|null $context): array
    {
        $defaultLegacyContext = null !== $this->defaultOptions ? $this->defaultOptions->toLegacyContext() : $this->defaultLegacyContext;

        if (null === $context) {
            return $defaultLegacyContext;
        }

        if (\is_array($context)) {
            trigger_deprecation('symfony/serializer', '6.1', 'Passing an array for $context is deprecated.');

            return [
                'csv_delimiter' => $context['csv_delimiter'] ?? $defaultLegacyContext['csv_delimiter'],
                'csv_enclosure' => $context['csv_enclosure'] ?? $defaultLegacyContext['csv_enclosure'],
                'csv_escape_char' => $context['csv_escape_char'] ?? $defaultLegacyContext['csv_escape_char'],
                'csv_key_separator' => $context['csv_key_separator'] ?? $defaultLegacyContext['csv_key_separator'],
                'csv_headers' => $context['csv_headers'] ?? $defaultLegacyContext['csv_headers'],
                'csv_escape_formulas' => $context['csv_escape_formulas'] ?? $defaultLegacyContext['csv_escape_formulas'],
                'as_collection' => $context['as_collection'] ?? $defaultLegacyContext['as_collection'],
                'no_headers' => $context['no_headers'] ?? $defaultLegacyContext['no_headers'],
                'csv_end_of_line' => $context['csv_end_of_line'] ?? $defaultLegacyContext['csv_end_of_line'],
                'output_utf8_bom' => $context['output_utf8_bom'] ?? $defaultLegacyContext['output_utf8_bom'],
            ];
        }

        // TODO comment in PR with like that and not using a simple "new" (allow references in context)
        // TODO test passing by reference
        // TODO comment in PR: prevent collision
        // TODO comment in PR: Allow early check
        // TODO comment in PR: Show how to do in 7.0 (in that class for example)
        return $this->getOptions($context)->toLegacyContext();
    }
}
