<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\Mapping\Decode;

use Symfony\Component\Encoder\Attribute\DecodeFormatter;
use Symfony\Component\Encoder\Attribute\EncodedName;
use Symfony\Component\Encoder\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\TypeInfo\Resolver\TypeResolverInterface;

/**
 * Enhances properties decoding metadata based on properties' attributes.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class AttributePropertyMetadataLoader implements PropertyMetadataLoaderInterface
{
    public function __construct(
        private PropertyMetadataLoaderInterface $decorated,
        private TypeResolverInterface $typeResolver,
    ) {
    }

    public function load(string $className, array $config, array $context): array
    {
        $initialResult = $this->decorated->load($className, $config, $context);
        $result = [];

        foreach ($initialResult as $initialEncodedName => $initialMetadata) {
            $attributesMetadata = $this->getPropertyAttributesMetadata(new \ReflectionProperty($className, $initialMetadata->getName()));
            $encodedName = $attributesMetadata['name'] ?? $initialEncodedName;

            if (null !== $formatter = $attributesMetadata['formatter'] ?? null) {
                $reflectionFormatter = new \ReflectionFunction(\Closure::fromCallable($formatter));
                $type = $this->typeResolver->resolve($reflectionFormatter->getParameters()[0]);

                $result[$encodedName] = $initialMetadata
                    ->withType($type)
                    ->withFormatter($formatter(...));

                continue;
            }

            $result[$encodedName] = $initialMetadata;
        }

        return $result;
    }

    /**
     * @return array{name?: string, formatter?: callable}
     */
    private function getPropertyAttributesMetadata(\ReflectionProperty $reflectionProperty): array
    {
        $metadata = [];

        $reflectionAttribute = $reflectionProperty->getAttributes(EncodedName::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
        if (null !== $reflectionAttribute) {
            $metadata['name'] = $reflectionAttribute->newInstance()->name;
        }

        $reflectionAttribute = $reflectionProperty->getAttributes(DecodeFormatter::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
        if (null !== $reflectionAttribute) {
            $metadata['formatter'] = $reflectionAttribute->newInstance()->formatter;
        }

        return $metadata;
    }
}
