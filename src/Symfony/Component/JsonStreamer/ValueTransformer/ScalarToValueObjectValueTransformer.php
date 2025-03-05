<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonStreamer\ValueTransformer;

use BcMath\Number;
use Symfony\Component\JsonStreamer\Exception\InvalidArgumentException;
use Symfony\Component\TypeInfo\Type;

/**
 * Transforms a scalar to its related value object.
 *
 * Does nothing if the value type is not valid.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 */
final class ScalarToValueObjectValueTransformer implements ValueTransformerInterface
{
    public const DATE_TIME_FORMAT_KEY = 'date_time_format';

    /**
     * @param class-string $valueObjectClassName
     */
    public function __construct(
        private string $valueObjectClassName,
    ) {
    }

    public function transform(mixed $value, array $options = []): mixed
    {
        if (\DateTimeInterface::class === $this->valueObjectClassName) {
            if (!\is_string($value)) {
                return $value;
            }

            return $this->transformToDateTime($value, $options[self::DATE_TIME_FORMAT_KEY] ?? null);
        }

        if (Number::class === $this->valueObjectClassName || \GMP::class === $this->valueObjectClassName) {
            if (!\is_string($value) && !\is_int($value)) {
                return $value;
            }

            return $this->transformToNumber($value);
        }

        throw new \LogicException(\sprintf('Unhandled "%s" value object.', $this->valueObjectClassName));
    }

    public static function getStreamValueType(): Type
    {
        return Type::union(Type::int(), Type::string());
    }

    private function transformToDateTime(string $value, ?string $dateTimeFormat): \DateTimeImmutable
    {
        if (null !== $dateTimeFormat) {
            if (false !== $dateTime = \DateTimeImmutable::createFromFormat($dateTimeFormat, $value)) {
                return $dateTime;
            }

            $dateTimeErrors = \DateTimeImmutable::getLastErrors();

            throw new InvalidArgumentException(\sprintf('Parsing datetime string "%s" using format "%s" resulted in %d errors: ', $value, $dateTimeFormat, $dateTimeErrors['error_count'])."\n".implode("\n", $this->formatDateTimeErrors($dateTimeErrors['errors'])));
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            $dateTimeErrors = \DateTimeImmutable::getLastErrors();

            throw new InvalidArgumentException(\sprintf('Parsing datetime string "%s" resulted in %d errors: ', $value, $dateTimeErrors['error_count'])."\n".implode("\n", $this->formatDateTimeErrors($dateTimeErrors['errors'])));
        }
    }

    private function transformToNumber(int|string $value): Number|\GMP
    {
        try {
            return new ($this->valueObjectClassName)($value);
        } catch (\ValueError $e) {
            throw new InvalidArgumentException(\sprintf('Unable to create a "%s"; the stream value must a parsable string or an int.', $this->valueObjectClassName), $e->getCode(), $e);
        }
    }

    /**
     * @param array<int, string> $errors
     *
     * @return list<string>
     */
    private function formatDateTimeErrors(array $errors): array
    {
        $formattedErrors = [];

        foreach ($errors as $pos => $message) {
            $formattedErrors[] = \sprintf('at position %d: %s', $pos, $message);
        }

        return $formattedErrors;
    }
}
