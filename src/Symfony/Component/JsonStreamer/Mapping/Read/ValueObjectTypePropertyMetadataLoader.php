<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonStreamer\Mapping\Read;

use BcMath\Number;
use Symfony\Component\JsonStreamer\Exception\InvalidArgumentException;
use Symfony\Component\JsonStreamer\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\UnionType;

/**
 * Transforms scalar to value object.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class ValueObjectTypePropertyMetadataLoader implements PropertyMetadataLoaderInterface
{
    public function __construct(
        private PropertyMetadataLoaderInterface $decorated,
    ) {
    }

    public function load(string $className, array $options = [], array $context = []): array
    {
        $result = $this->decorated->load($className, $options, $context);

        foreach ($result as &$metadata) {
            $type = $metadata->getType();
            $newTypeParts = [];

            foreach ($type instanceof UnionType ? $type->getTypes() : [$type] as $t) {
                $newTypePart = $t;

                if ($t->isIdentifiedBy(\DateTimeInterface::class)) {
                    if ($t->isIdentifiedBy(\DateTime::class)) {
                        throw new InvalidArgumentException('The "DateTime" class is not supported. Use "DateTimeImmutable" instead.');
                    }

                    $metadata = $metadata->withAdditionalStreamToNativeValueTransformer('json_streamer.value_transformer.scalar_to_date_time');
                    $newTypePart = Type::string();
                } elseif ($t->isIdentifiedBy(Number::class)) {
                    $metadata = $metadata->withAdditionalStreamToNativeValueTransformer('json_streamer.value_transformer.scalar_to_bc_math_number');
                    $newTypePart = Type::union(Type::int(), Type::string());
                } elseif ($t->isIdentifiedBy(\GMP::class)) {
                    $metadata = $metadata->withAdditionalStreamToNativeValueTransformer('json_streamer.value_transformer.scalar_to_gmp_number');
                    $newTypePart = Type::union(Type::int(), Type::string());
                }

                $newTypeParts = [...$newTypeParts, ...($newTypePart instanceof UnionType ? $newTypePart->getTypes() : [$newTypePart])];
            }

            $newTypeParts = array_values(array_unique($newTypeParts));
            $newType = \count($newTypeParts) > 1 ? Type::union(...$newTypeParts) : $newTypeParts[0];

            $metadata = $metadata->withType($newType);
        }

        return $result;
    }
}
