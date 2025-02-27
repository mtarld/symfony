<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\ValueTransformer;

use BcMath\Number;
use Symfony\Component\JsonEncoder\Exception\InvalidArgumentException;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * Transforms number object to string during encoding.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 */
final class NumberToStringValueTransformer implements ValueTransformerInterface
{
    public function transform(mixed $value, array $options = []): string
    {
        if (!$value instanceof Number && !$value instanceof \GMP) {
            throw new InvalidArgumentException(\sprintf('The native value must be either a "%s" or a "%s".', Number::class, \GMP::class));
        }

        return (string) $value;
    }

    /**
     * @return BuiltinType<TypeIdentifier::STRING>
     */
    public static function getJsonValueType(): BuiltinType
    {
        return Type::string();
    }
}
