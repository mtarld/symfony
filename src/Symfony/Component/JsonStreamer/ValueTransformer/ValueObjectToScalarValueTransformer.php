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
use Symfony\Component\TypeInfo\Type;

/**
 * Transforms a value object to its related scalar value during stream writing.
 *
 * Does nothing if the value is not a valid value object.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 */
final class ValueObjectToScalarValueTransformer implements ValueTransformerInterface
{
    public const DATE_TIME_FORMAT_KEY = 'date_time_format';

    public function transform(mixed $value, array $options = []): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format($options[self::DATE_TIME_FORMAT_KEY] ?? \DateTimeInterface::RFC3339);
        }

        if ($value instanceof Number || $value instanceof \GMP) {
            return (string) $value;
        }

        return $value;
    }

    public static function getStreamValueType(): Type
    {
        return Type::string();
    }
}
