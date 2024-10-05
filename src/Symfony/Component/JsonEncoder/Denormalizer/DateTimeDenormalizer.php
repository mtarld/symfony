<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Denormalizer;

use Symfony\Component\JsonEncoder\Denormalizer\DenormalizerInterface;
use Symfony\Component\JsonEncoder\Exception\InvalidArgumentException;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\TypeInfo\Type\BuiltinType;

/**
 * Casts string to DateTimeInterface.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class DateTimeDenormalizer implements DenormalizerInterface
{
    public const FORMAT_KEY = 'date_time_format';

    public function denormalize(mixed $normalized, array $config): mixed
    {
        if (!\is_string($normalized) || '' === trim($normalized)) {
            throw new InvalidArgumentException('The normalized data is either not an string, or an empty string, or null; you should pass a string that can be parsed with the passed format or a valid DateTime string.');
        }

        $dateTimeFormat = $config[self::FORMAT_KEY] ?? null;

        if (null !== $dateTimeFormat) {
            if (false !== $dateTime = \DateTimeImmutable::createFromFormat($dateTimeFormat, $normalized)) {
                return $dateTime;
            }

            $dateTimeErrors = \DateTimeImmutable::getLastErrors();

            throw new InvalidArgumentException(\sprintf('Parsing datetime string "%s" using format "%s" resulted in %d errors: ', $normalized, $dateTimeFormat, $dateTimeErrors['error_count'])."\n".implode("\n", $this->formatDateTimeErrors($dateTimeErrors['errors'])));
        }

        try {
            return new \DateTimeImmutable($normalized);
        } catch (\Throwable) {
            $dateTimeErrors = \DateTimeImmutable::getLastErrors();

            throw new InvalidArgumentException(\sprintf('Parsing datetime string "%s" resulted in %d errors: ', $normalized, $dateTimeErrors['error_count'])."\n".implode("\n", $this->formatDateTimeErrors($dateTimeErrors['errors'])));
        }
    }

    /**
     * @return Type<BuiltinType<TypeIdentifier::STRING>>
     */
    public static function getNormalizedType(): Type
    {
        return Type::string();
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
            $formattedErrors[] = \sprintf('at position %d: %s', $pos, $message);
        }

        return $formattedErrors;
    }
}
