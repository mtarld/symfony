<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Marshal\Mapping;

use Symfony\Component\JsonMarshaller\Attribute\MarshalFormatter;
use Symfony\Component\JsonMarshaller\Attribute\MarshalledName;
use Symfony\Component\JsonMarshaller\Attribute\MaxDepth;
use Symfony\Component\JsonMarshaller\Type\TypeExtractorInterface;

/**
 * Enhances properties marshalling metadata based on properties' attributes.
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

            if (isset($attributesMetadata['max_depth']) && ($context['depth_counters'][$className] ?? 0) > $attributesMetadata['max_depth']) {
                if (null === $formatter = $attributesMetadata['max_depth_reached_formatter'] ?? null) {
                    continue;
                }

                $reflectionFormatter = new \ReflectionFunction(\Closure::fromCallable($formatter));
                $type = $this->typeExtractor->extractTypeFromFunctionReturn($reflectionFormatter);

                $result[$marshalledName] = $initialMetadata
                    ->withType($type)
                    ->withFormatter($formatter(...));

                continue;
            }

            if (null !== $formatter = $attributesMetadata['formatter'] ?? null) {
                $reflectionFormatter = new \ReflectionFunction(\Closure::fromCallable($formatter));
                $type = $this->typeExtractor->extractTypeFromFunctionReturn($reflectionFormatter);

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
     * @return array{name?: string, formatter?: callable, max_depth?: int, max_depth_reached_formatter?: ?callable}
     */
    private function propertyAttributesMetadata(\ReflectionProperty $reflectionProperty): array
    {
        $metadata = [];

        $reflectionAttribute = $reflectionProperty->getAttributes(MarshalledName::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
        if (null !== $reflectionAttribute) {
            $metadata['name'] = $reflectionAttribute->newInstance()->name;
        }

        $reflectionAttribute = $reflectionProperty->getAttributes(MarshalFormatter::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
        if (null !== $reflectionAttribute) {
            $metadata['formatter'] = $reflectionAttribute->newInstance()->formatter;
        }

        $reflectionAttribute = $reflectionProperty->getAttributes(MaxDepth::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
        if (null !== $reflectionAttribute) {
            $attributeInstance = $reflectionAttribute->newInstance();

            $metadata['max_depth'] = $attributeInstance->maxDepth;
            $metadata['max_depth_reached_formatter'] = $attributeInstance->maxDepthReachedFormatter;
        }

        return $metadata;
    }
}
