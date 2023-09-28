<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Unmarshal\Mapping;

use Symfony\Component\JsonMarshaller\Attribute\MarshalledName;
use Symfony\Component\JsonMarshaller\Attribute\UnmarshalFormatter;
use Symfony\Component\JsonMarshaller\Type\TypeExtractorInterface;

/**
 * Enhances properties unmarshalling metadata based on properties' attributes.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class AttributePropertyMetadataLoader implements PropertyMetadataLoaderInterface
{
    public function __construct(
        private readonly PropertyMetadataLoaderInterface $decorated,
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
    }

    public function load(string $className, array $config, array $context): array
    {
        $initialResult = $this->decorated->load($className, $config, $context);
        $result = [];

        foreach ($initialResult as $initialMarshalledName => $initialMetadata) {
            $attributesMetadata = $this->propertyAttributesMetadata(new \ReflectionProperty($className, $initialMetadata->name()));
            $marshalledName = $attributesMetadata['name'] ?? $initialMarshalledName;

            if (null !== $formatter = $attributesMetadata['formatter'] ?? null) {
                $reflectionFormatter = new \ReflectionFunction(\Closure::fromCallable($formatter));
                $type = $this->typeExtractor->extractTypeFromParameter($reflectionFormatter->getParameters()[0]);

                $result[$marshalledName] = $initialMetadata
                    ->withType($type)
                    ->withFormatter($formatter(...));

                continue;
            }

            $result[$marshalledName] = $initialMetadata;
        }

        return $result;
    }

    /**
     * @return array{name?: string, formatter?: callable}
     */
    private function propertyAttributesMetadata(\ReflectionProperty $reflectionProperty): array
    {
        $metadata = [];

        $reflectionAttribute = $reflectionProperty->getAttributes(MarshalledName::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
        if (null !== $reflectionAttribute) {
            $metadata['name'] = $reflectionAttribute->newInstance()->name;
        }

        $reflectionAttribute = $reflectionProperty->getAttributes(UnmarshalFormatter::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
        if (null !== $reflectionAttribute) {
            $metadata['formatter'] = $reflectionAttribute->newInstance()->formatter;
        }

        return $metadata;
    }
}
