<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Normalizer;

use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Context\Context;
use Symfony\Component\Serializer\Context\Normalizer\DateTimeNormalizerOptions;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

/**
 * Normalizes an object implementing the {@see \DateTimeInterface} to a date string.
 * Denormalizes a date string to an instance of {@see \DateTime} or {@see \DateTimeImmutable}.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DateTimeNormalizer implements NormalizerInterface, DenormalizerInterface, CacheableSupportsMethodInterface
{
    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const FORMAT_KEY = 'datetime_format';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const TIMEZONE_KEY = 'datetime_timezone';

    private const SUPPORTED_TYPES = [
        \DateTimeInterface::class => true,
        \DateTimeImmutable::class => true,
        \DateTime::class => true,
    ];

    private ?array $defaultLegacyContext = null;

    private ?DateTimeNormalizerOptions $defaultOptions = null;

    /**
     * @param Context|null $defaultContext
     */
    public function __construct(/* Context $defaultContext = null */)
    {
        /** @var Context|array|null $defaultContext */
        $defaultContext = 0 < \func_num_args() ? \func_get_arg(0) : null;

        if (\is_array($defaultContext)) {
            trigger_deprecation('symfony/serializer', '6.1', 'Passing an array for $defaultContext is deprecated.');
            $this->defaultLegacyContext = array_merge((new DateTimeNormalizerOptions())->toLegacyContext(), $defaultContext);

            return;
        }

        $this->defaultOptions = $defaultContext?->getOptions(DateTimeNormalizerOptions::class) ?? new DateTimeNormalizerOptions();
    }

    /**
     * {@inheritdoc}
     *
     * @param Context|null $context
     *
     * @throws InvalidArgumentException
     */
    public function normalize(mixed $object, string $format = null /*, Context $context = null */): string
    {
        $context = $this->getContext(2 < \func_num_args() ? \func_get_arg(2) : null);

        if (!$object instanceof \DateTimeInterface) {
            throw new InvalidArgumentException('The object must implement the "\DateTimeInterface".');
        }

        // TODO test it
        $defaultContext = $this->defaultOptions ? $this->defaultOptions->toLegacyContext() : $this->defaultLegacyContext;
        $dateTimeFormat = $context['datetime_format'] ?? $defaultContext['datetime_format'] ?? \DateTime::RFC3339;
        $timezone = $this->getTimezone($context);

        if (null !== $timezone) {
            $object = clone $object;
            $object = $object->setTimezone($timezone);
        }

        return $object->format($dateTimeFormat);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof \DateTimeInterface;
    }

    /**
     * {@inheritdoc}
     *
     * @param Context|null $context
     *
     * @throws NotNormalizableValueException
     */
    public function denormalize(mixed $data, string $type, string $format = null /*, Context $context = null */): \DateTimeInterface
    {
        $context = $this->getContext(3 < \func_num_args() ? \func_get_arg(3) : null);
        $timezone = $this->getTimezone($context);

        if (null === $data || (\is_string($data) && '' === trim($data))) {
            throw NotNormalizableValueException::createForUnexpectedDataType('The data is either an empty string or null, you should pass a string that can be parsed with the passed format or a valid DateTime string.', $data, [Type::BUILTIN_TYPE_STRING], $context['deserialization_path'] ?? null, true);
        }

        if (null !== $context['datetime_format']) {
            $object = \DateTime::class === $type ? \DateTime::createFromFormat($context['datetime_format'], $data, $timezone) : \DateTimeImmutable::createFromFormat($context['datetime_format'], $data, $timezone);

            if (false !== $object) {
                return $object;
            }

            $dateTimeErrors = \DateTime::class === $type ? \DateTime::getLastErrors() : \DateTimeImmutable::getLastErrors();

            throw NotNormalizableValueException::createForUnexpectedDataType(sprintf('Parsing datetime string "%s" using format "%s" resulted in %d errors: ', $data, $context['datetime_format'], $dateTimeErrors['error_count'])."\n".implode("\n", $this->formatDateTimeErrors($dateTimeErrors['errors'])), $data, [Type::BUILTIN_TYPE_STRING], $context['deserialization_path'] ?? null, true);
        }

        try {
            return \DateTime::class === $type ? new \DateTime($data, $timezone) : new \DateTimeImmutable($data, $timezone);
        } catch (\Exception $e) {
            throw NotNormalizableValueException::createForUnexpectedDataType($e->getMessage(), $data, [Type::BUILTIN_TYPE_STRING], $context['deserialization_path'] ?? null, false, $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return isset(self::SUPPORTED_TYPES[$type]);
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return __CLASS__ === static::class;
    }

    /**
     * Formats datetime errors.
     *
     * @return string[]
     */
    private function formatDateTimeErrors(array $errors): array
    {
        $formattedErrors = [];

        foreach ($errors as $pos => $message) {
            $formattedErrors[] = sprintf('at position %d: %s', $pos, $message);
        }

        return $formattedErrors;
    }

    private function getTimezone(array $context): ?\DateTimeZone
    {
        $dateTimeZone = $context['datetime_timezone'];

        if (null === $dateTimeZone) {
            return null;
        }

        return $dateTimeZone instanceof \DateTimeZone ? $dateTimeZone : new \DateTimeZone($dateTimeZone);
    }

    private function getOptions(?Context $context): DateTimeNormalizerOptions
    {
        $options = $context?->getOptions(DateTimeNormalizerOptions::class);

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
                'datetime_format' => $context['datetime_format'] ?? $defaultLegacyContext['datetime_format'],
                'datetime_timezone' => $context['datetime_timezone'] ?? $defaultLegacyContext['datetime_timezone'],
            ];
        }

        return $this->getOptions($context)->toLegacyContext();
    }
}
