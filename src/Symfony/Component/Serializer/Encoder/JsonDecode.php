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
use Symfony\Component\Serializer\Context\Encoder\JsonEncoderOptions;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

/**
 * Decodes JSON data.
 *
 * @author Sander Coolen <sander@jibber.nl>
 */
class JsonDecode implements DecoderInterface
{
    protected $serializer;

    /**
     * True to return the result as an associative array, false for a nested stdClass hierarchy.
     *
     * @deprecated since symfony/serializer 6.1, use Context instead
     */
    public const ASSOCIATIVE = 'json_decode_associative';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const OPTIONS = 'json_decode_options';

    /**
     * Specifies the recursion depth.
     *
     * @deprecated since symfony/serializer 6.1, use Context instead
     */
    public const RECURSION_DEPTH = 'json_decode_recursion_depth';

    private ?JsonEncoderOptions $defaultOptions = null;

    private ?array $defaultLegacyContext = null;

    /**
     * @param Context|null $defaultContext
     */
    public function __construct(/* Context $defaultContext = null */)
    {
        /** @var Context|array|null $defaultContext */
        // TODO test it
        $defaultContext = 0 < \func_num_args() ? \func_get_arg(0) : null;

        if (\is_array($defaultContext)) {
            trigger_deprecation('symfony/serializer', '6.1', 'Passing an array for $defaultContext is deprecated.');
            $this->defaultLegacyContext = array_merge((new JsonEncoderOptions())->toLegacyContext(), $defaultContext);

            return;
        }

        $this->defaultOptions = $defaultContext?->getOptions(JsonEncoderOptions::class) ?? new JsonEncoderOptions();
    }

    /**
     * Decodes data.
     *
     * @param string $data    The encoded JSON string to decode
     * @param string $format  Must be set to JsonEncoder::FORMAT
     * @param array  $context An optional set of options for the JSON decoder; see below
     *
     * The $context array is a simple key=>value array, with the following supported keys:
     *
     * json_decode_associative: boolean
     *      If true, returns the object as an associative array.
     *      If false, returns the object as nested stdClass
     *      If not specified, this method will use the default set in JsonDecode::__construct
     *
     * json_decode_recursion_depth: integer
     *      Specifies the maximum recursion depth
     *      If not specified, this method will use the default set in JsonDecode::__construct
     *
     * json_decode_options: integer
     *      Specifies additional options as per documentation for json_decode
     *
     * @param Context|null $context
     *
     * @throws NotEncodableValueException
     *
     * @see https://php.net/json_decode
     */
    public function decode(string $data, string $format /*, Context $context = null */): mixed
    {
        $context = $this->getContext(2 < \func_num_args() ? \func_get_arg(2) : null);

        try {
            $decodedData = json_decode($data, $context['json_decode_associative'], $context['json_decode_recursion_depth'], $context['json_decode_options']);
        } catch (\JsonException $e) {
            throw new NotEncodableValueException($e->getMessage(), 0, $e);
        }

        if (\JSON_THROW_ON_ERROR & $context['json_decode_options']) {
            return $decodedData;
        }

        if (\JSON_ERROR_NONE !== json_last_error()) {
            throw new NotEncodableValueException(json_last_error_msg());
        }

        return $decodedData;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDecoding(string $format): bool
    {
        return JsonEncoder::FORMAT === $format;
    }

    private function getOptions(?Context $context): JsonEncoderOptions
    {
        $options = $context?->getOptions(JsonEncoderOptions::class);

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
                'json_encode_options' => $context['json_encode_options'] ?? $defaultLegacyContext['json_encode_options'],
                'json_decode_options' => $context['json_decode_options'] ?? $defaultLegacyContext['json_decode_options'],
                'json_decode_associative' => $context['json_decode_associative'] ?? $defaultLegacyContext['json_decode_associative'],
                'json_decode_recursion_depth' => $context['json_decode_recursion_depth'] ?? $defaultLegacyContext['json_decode_recursion_depth'],
            ];
        }

        return $this->getOptions($context)->toLegacyContext();
    }
}
