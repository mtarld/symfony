<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonStreamer\Mapping\Write;

use BcMath\Number;
use Symfony\Component\JsonStreamer\Exception\InvalidArgumentException;
use Symfony\Component\JsonStreamer\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\UnionType;

/**
 * Transforms value object to scalar.
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

            $hasValueObject = false;

            foreach ($type instanceof UnionType ? $type->getTypes() : [$type] as $t) {
                $newTypePart = $t;

                if ($t->isIdentifiedBy(\DateTimeInterface::class, Number::class, \GMP::class)) {
                    if ($t->isIdentifiedBy(\DateTime::class)) {
                        throw new InvalidArgumentException('The "DateTime" class is not supported. Use "DateTimeImmutable" instead.');
                    }

                    $hasValueObject = true;
                    $newTypePart = Type::string();
                }

                $newTypeParts[] = $newTypePart;
            }

            if (!$hasValueObject) {
                continue;
            }

            $newTypeParts = array_values(array_unique($newTypeParts));
            $newType = \count($newTypeParts) > 1 ? Type::union(...$newTypeParts) : $newTypeParts[0];

            $metadata = $metadata
                ->withType($newType)
                ->withAdditionalNativeToStreamValueTransformer('json_streamer.value_transformer.value_object_to_scalar');
        }

        return $result;
    }
}
