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

use Symfony\Component\Serializer\Context\Context;
use Symfony\Component\Serializer\Context\Normalizer\DateIntervalNormalizerOptions;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Normalizes an instance of {@see \DateInterval} to an interval string.
 * Denormalizes an interval string to an instance of {@see \DateInterval}.
 *
 * @author Jérôme Parmentier <jerome@prmntr.me>
 */
class DateIntervalNormalizer implements NormalizerInterface, DenormalizerInterface, CacheableSupportsMethodInterface
{
    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const FORMAT_KEY = 'dateinterval_format';

    private ?array $defaultLegacyContext = null;

    private ?DateIntervalNormalizerOptions $defaultOptions = null;

    /**
     * @param Context|null $defaultContext
     */
    public function __construct(/* Context $defaultContext = null */)
    {
        /** @var Context|array|null $defaultContext */
        $defaultContext = 0 < \func_num_args() ? \func_get_arg(0) : null;

        if (\is_array($defaultContext)) {
            trigger_deprecation('symfony/serializer', '6.1', 'Passing an array for $defaultContext is deprecated.');
            $this->defaultLegacyContext = array_merge((new DateIntervalNormalizerOptions())->toLegacyContext(), $defaultContext);

            return;
        }

        $this->defaultOptions = $defaultContext?->getOptions(DateIntervalNormalizerOptions::class) ?? new DateIntervalNormalizerOptions();
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

        if (!$object instanceof \DateInterval) {
            throw new InvalidArgumentException('The object must be an instance of "\DateInterval".');
        }

        return $object->format($context['dateinterval_format']);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $data instanceof \DateInterval;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return __CLASS__ === static::class;
    }

    /**
     * {@inheritdoc}
     *
     * @param Context|null $context
     *
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function denormalize(mixed $data, string $type, string $format = null /*, Context $context = null */): \DateInterval
    {
        $context = $this->getContext(3 < \func_num_args() ? \func_get_arg(3) : null);

        if (!\is_string($data)) {
            throw new InvalidArgumentException(sprintf('Data expected to be a string, "%s" given.', get_debug_type($data)));
        }

        if (!$this->isISO8601($data)) {
            throw new UnexpectedValueException('Expected a valid ISO 8601 interval string.');
        }

        $dateIntervalFormat = $context['dateinterval_format'];

        $signPattern = '';
        switch (substr($dateIntervalFormat, 0, 2)) {
            case '%R':
                $signPattern = '[-+]';
                $dateIntervalFormat = substr($dateIntervalFormat, 2);
                break;
            case '%r':
                $signPattern = '-?';
                $dateIntervalFormat = substr($dateIntervalFormat, 2);
                break;
        }
        $valuePattern = '/^'.$signPattern.preg_replace('/%([yYmMdDhHiIsSwW])(\w)/', '(?:(?P<$1>\d+)$2)?', preg_replace('/(T.*)$/', '($1)?', $dateIntervalFormat)).'$/';
        if (!preg_match($valuePattern, $data)) {
            throw new UnexpectedValueException(sprintf('Value "%s" contains intervals not accepted by format "%s".', $data, $dateIntervalFormat));
        }

        try {
            if ('-' === $data[0]) {
                $interval = new \DateInterval(substr($data, 1));
                $interval->invert = 1;

                return $interval;
            }

            if ('+' === $data[0]) {
                return new \DateInterval(substr($data, 1));
            }

            return new \DateInterval($data);
        } catch (\Exception $e) {
            throw new UnexpectedValueException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return \DateInterval::class === $type;
    }

    private function isISO8601(string $string): bool
    {
        return preg_match('/^[\-+]?P(?=\w*(?:\d|%\w))(?:\d+Y|%[yY]Y)?(?:\d+M|%[mM]M)?(?:(?:\d+D|%[dD]D)|(?:\d+W|%[wW]W))?(?:T(?:\d+H|[hH]H)?(?:\d+M|[iI]M)?(?:\d+S|[sS]S)?)?$/', $string);
    }

    private function getOptions(?Context $context): DateIntervalNormalizerOptions
    {
        $options = $context?->getOptions(DateIntervalNormalizerOptions::class);

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
                'dateinterval_format' => $context['dateinterval_format'] ?? $defaultLegacyContext['dateinterval_format'],
            ];
        }

        return $this->getOptions($context)->toLegacyContext();
    }
}
