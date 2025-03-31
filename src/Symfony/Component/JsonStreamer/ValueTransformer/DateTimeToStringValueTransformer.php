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

use Symfony\Component\TypeInfo\Type;

/**
 * Transforms DateTimeInterface to string during stream writing.
 *
 * Does nothing if the native value is not a valid object.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 */
final class DateTimeToStringValueTransformer implements ValueTransformerInterface
{
    public const FORMAT_KEY = 'date_time_format';

    public function transform(mixed $value, array $options = []): mixed
    {
        if (!$value instanceof \DateTimeInterface) {
            return $value;
        }

        return $value->format($options[self::FORMAT_KEY] ?? \DateTimeInterface::RFC3339);
    }

    public static function getStreamValueType(): Type
    {
        return Type::string();
    }
}
