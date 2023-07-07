<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Mapping;

use Symfony\Component\Serializer\Attribute\DeserializeFormatter;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Deserialize\Configuration\Configuration;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;
use Symfony\Component\Serializer\Type\TypeGenericsHelper;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class PropertyMetadataLoader implements PropertyMetadataLoaderInterface
{
    private static array $cache = [
        'metadata' => [],
        'reflection' => [],
        'formatter_reflection' => [],
        'generic_types' => [],
        'type' => [],
    ];

    private readonly TypeGenericsHelper $typeGenericsHelper;

    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
        $this->typeGenericsHelper = new TypeGenericsHelper();
    }

    public function load(string $className, Configuration $configuration, array $context): array
    {
        $result = [];

        $reflectionClass = self::$cache['reflection'][$className] = new \ReflectionClass($className);
        $genericTypes = self::$cache['generic_types'][$className.$context['original_type']] ??= $this->genericTypes($className, $context['original_type']);

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $cacheKey = $className.$reflectionProperty->getName();
            $metadata = (self::$cache['metadata'][$cacheKey] ??= ($this->propertyMetadata($reflectionProperty) ?? []));

            if ([] !== $configuration->groups()) {
                $matchingGroup = false;
                foreach ($configuration->groups() as $group) {
                    if (isset($metadata['groups'][$group])) {
                        $matchingGroup = true;

                        break;
                    }
                }

                if (!$matchingGroup) {
                    continue;
                }
            }

            $name = self::$cache['metadata'][$cacheKey]['name'] ?? $reflectionProperty->getName();

            if (null === $formatter = $metadata['formatter'] ?? null) {
                $type = (self::$cache['type'][$cacheKey] ??= $this->typeExtractor->extractFromProperty($reflectionProperty));
                if (isset($genericTypes[(string) $type])) {
                    $type = $this->typeGenericsHelper->replaceGenericTypes($type, $genericTypes);
                }

                $result[$name] = new PropertyMetadata(
                    name: $reflectionProperty->getName(),
                    valueProvider: fn (callable $valueProvider): mixed => $valueProvider($type),
                );

                continue;
            }

            $cacheKey .= json_encode($formatter);
            $formatterReflection = self::$cache['formatter_reflection'][$cacheKey] ??= new \ReflectionFunction(\Closure::fromCallable($formatter));
            $type = self::$cache['type'][$cacheKey] ??= $this->typeExtractor->extractFromFunctionParameter($formatterReflection->getParameters()[0]);

            if (isset($genericTypes[(string) $type]) && $formatterReflection->getClosureScopeClass()?->getName() !== $className) {
                $type = $this->typeGenericsHelper->replaceGenericTypes($type, $genericTypes);
            }

            $result[$name] = new PropertyMetadata(
                name: $reflectionProperty->getName(),
                valueProvider: fn (callable $valueProvider): mixed => $formatter($valueProvider($type)),
            );
        }

        return $result;
    }

    /**
     * @return array{groups?: array<string, true>, name?: string, formatter?: callable(mixed): mixed}
     */
    private function propertyMetadata(\ReflectionProperty $reflection): array
    {
        $metadata = [];

        foreach ($reflection->getAttributes() as $attribute) {
            if (Groups::class === $attribute->getName()) {
                /** @var Groups $attributeInstance */
                $attributeInstance = $attribute->newInstance();
                foreach ($attributeInstance->groups as $group) {
                    $metadata['groups'][$group] = true;
                }

                continue;
            }

            if (SerializedName::class === $attribute->getName()) {
                /** @var SerializedName $attributeInstance */
                $attributeInstance = $attribute->newInstance();
                $metadata['name'] = $attributeInstance->name;

                continue;
            }

            if (DeserializeFormatter::class === $attribute->getName()) {
                /** @var DeserializeFormatter $attributeInstance */
                $attributeInstance = $attribute->newInstance();
                $metadata['formatter'] = $attributeInstance->formatter;

                continue;
            }
        }

        return $metadata;
    }

    /**
     * @param class-string $className
     *
     * @return array<string, Type>
     */
    private function genericTypes(string $className, Type $type): array
    {
        $findClassType = static function (string $className, Type $type) use (&$findClassType): ?Type {
            if ($type->hasClass() && $type->className() === $className) {
                return $type;
            }

            foreach ($type->genericParameterTypes() as $genericParameterType) {
                if (null !== $t = $findClassType($className, $genericParameterType)) {
                    return $t;
                }
            }

            foreach ($type->unionTypes() as $unionType) {
                if (null !== $t = $findClassType($className, $unionType)) {
                    return $t;
                }
            }

            return null;
        };

        $classType = $findClassType($className, $type);

        $genericParameterTypes = $classType->genericParameterTypes();
        $templates = $this->typeExtractor->extractTemplateFromClass(new \ReflectionClass($className));

        if (\count($templates) !== \count($genericParameterTypes)) {
            throw new InvalidArgumentException(sprintf(
                'Given %d generic parameters in "%s", but %d templates are defined in "%2$s".',
                \count($genericParameterTypes),
                $className,
                \count($templates),
            ));
        }

        $genericTypes = [];
        foreach ($genericParameterTypes as $i => $genericParameterType) {
            $genericTypes[$templates[$i]] = $genericParameterType;
        }

        return $genericTypes;
    }
}
