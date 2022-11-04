<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Metadata;

final class ClassMetadataFactory
{
    /**
     * @param class-string $className
     */
    public static function create(string $className, array $creationContext = []): array
    {
        $reflectionClass = new \ReflectionClass($className);

        $properties = array_map(
            fn (\ReflectionProperty $p): array => static::createPropertyMetadata($p, $creationContext),
            $reflectionClass->getProperties(),
        );

        return new ClassMetadata(
            $class->getName(),
            $this->propertyFilterer->filter($properties, $context),
        );
    }

    private static function createPropertyMetadata(\ReflectionProperty $reflectionProperty, array $creationContext): array
    {
    }
}
