<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Mapping\Decode;

use BcMath\Number;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\JsonEncoder\ValueTransformer\ScalarToNumberValueTransformer;

/**
 * Transforms scalar to number object for properties with number object type.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class NumberTypePropertyMetadataLoader implements PropertyMetadataLoaderInterface
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

            if ($type->isIdentifiedBy(Number::class)) {
                $metadata = $metadata
                    ->withType(ScalarToNumberValueTransformer::getJsonValueType())
                    ->withAdditionalToNativeValueTransformer('json_encoder.value_transformer.scalar_to_bc_math_number');
            } elseif ($type->isIdentifiedBy(\GMP::class)) {
                $metadata = $metadata
                    ->withType(ScalarToNumberValueTransformer::getJsonValueType())
                    ->withAdditionalToNativeValueTransformer('json_encoder.value_transformer.scalar_to_gmp_number');
            }
        }

        return $result;
    }
}
