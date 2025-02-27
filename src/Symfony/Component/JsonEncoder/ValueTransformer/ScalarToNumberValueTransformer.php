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
 * Transforms string to DateTimeImmutable during decoding.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 */
final class ScalarToNumberValueTransformer implements ValueTransformerInterface
{
    /**
     * @param class-string<Number|\GMP> $numberClassName
     */
    public function __construct(
        private string $numberClassName,
    ) {
    }

    public function transform(mixed $value, array $options = []): Number|\GMP
    {
        if (!\is_string($value) && !\is_int($value)) {
            throw new InvalidArgumentException('The stream value is neither a string nor an int; you should pass a parsable string or an int.');
        }

        try {
            return match ($this->numberClassName) {
                Number::class => new Number($value),
                \GMP::class => new \GMP($value),
                default => throw new \LogicException(\sprintf('Only "%s" and "%s" types are supported by "%s()".', Number::class, \GMP::class, __METHOD__)),
            };
        } catch (\ValueError $e) {
            throw new InvalidArgumentException(\sprintf('Unable to create a "%s"; the stream value must a parsable string or an int.', $this->numberClassName), $e->getCode(), $e);
        }
    }

    /**
     * @return BuiltinType<TypeIdentifier::STRING>
     */
    public static function getJsonValueType(): BuiltinType
    {
        return Type::string();
    }
}
