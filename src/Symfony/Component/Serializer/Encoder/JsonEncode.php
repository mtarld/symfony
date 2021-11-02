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
 * Encodes JSON data.
 *
 * @author Sander Coolen <sander@jibber.nl>
 */
class JsonEncode implements EncoderInterface
{
    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const OPTIONS = 'json_encode_options';

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
     * {@inheritdoc}
     *
     * @param Context|null $context
     */
    public function encode(mixed $data, string $format /*, Context $context = null */): string
    {
        $context = $this->getContext(2 < \func_num_args() ? \func_get_arg(2) : null);

        try {
            $encodedJson = json_encode($data, $context['json_encode_options']);
        } catch (\JsonException $e) {
            throw new NotEncodableValueException($e->getMessage(), 0, $e);
        }

        if (\JSON_THROW_ON_ERROR & $context['json_encode_options']) {
            return $encodedJson;
        }

        if (\JSON_ERROR_NONE !== json_last_error() && (false === $encodedJson || !($context['json_encode_options'] & \JSON_PARTIAL_OUTPUT_ON_ERROR))) {
            throw new NotEncodableValueException(json_last_error_msg());
        }

        return $encodedJson;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEncoding(string $format): bool
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
