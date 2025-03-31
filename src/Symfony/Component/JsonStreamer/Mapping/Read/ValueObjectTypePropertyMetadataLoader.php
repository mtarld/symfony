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

use Symfony\Component\JsonStreamer\Exception\InvalidArgumentException;
use Symfony\Component\JsonStreamer\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\JsonStreamer\ValueTransformer\ScalarToValueObjectValueTransformer;
use Symfony\Component\JsonStreamer\ValueTransformer\StringToDateTimeValueTransformer;
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
            $nativeType = $metadata->getNativeType();

            if ($nativeType->isIdentifiedBy(\DateTime::class)) {
                throw new InvalidArgumentException('The "DateTime" class is not supported. Use "DateTimeImmutable" instead.');
            }

            if ($nativeType instanceof UnionType && $nativeType->isIdentifiedBy(\DateTimeInterface::class)) {
                $metadata = $metadata
                    ->withAdditionalStreamToNativeValueTransformer('json_streamer.value_transformer.scalar_to_value_object')
                    ->withStreamType(ScalarToValueObjectValueTransformer::getStreamValueType());

                continue;
            }

            if ($nativeType->isIdentifiedBy(\DateTimeInterface::class)) {
                $metadata = $metadata
                    ->withAdditionalStreamToNativeValueTransformer('json_streamer.value_transformer.string_to_date_time')
                    ->withStreamType(StringToDateTimeValueTransformer::getStreamValueType());

                continue;
            }
        }

        return $result;
    }
}
