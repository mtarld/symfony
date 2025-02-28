<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Mapping\Encode;

use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\JsonEncoder\ValueTransformer\DateTimeToStringValueTransformer;

/**
 * Transforms DateTimeInterface to string for properties with DateTimeInterface type.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class DateTimeTypePropertyMetadataLoader implements PropertyMetadataLoaderInterface
{
    public function __construct(
        private PropertyMetadataLoaderInterface $decorated,
    ) {
    }

    public function load(string $className, array $options = [], array $context = []): array
    {
        $result = $this->decorated->load($className, $options, $context);

        foreach ($result as &$metadata) {
            $types = $updatedTypes = $metadata->getTypes();

            foreach ($types as $i => $type) {
                if (!$type['native']->isIdentifiedBy(\DateTimeInterface::class)) {
                    continue;
                }

                $updatedTypes[$i]['json'] = DateTimeToStringValueTransformer::getJsonValueType();

                $metadata = $metadata->withToJsonValueTransformer($type['native'], 'json_encoder.value_transformer.date_time_to_string');
            }

            $metadata = $metadata->withTypes($updatedTypes);
        }

        return $result;
    }
}
