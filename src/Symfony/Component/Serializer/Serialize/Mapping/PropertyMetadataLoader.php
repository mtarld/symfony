<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Mapping;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializeFormatter;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Serialize\Configuration;
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
        'generic_types' => [],
        'type' => [],
    ];

    private readonly TypeGenericsHelper $typeGenericsHelper;

    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
        $this->typeGenericsHelper = new TypeGenericsHelper();
    }

    public function load(string $className, Configuration $configuration, array $runtime): array
    {
        $result = [];

        $reflectionClass = self::$cache['reflection'][$className] = new \ReflectionClass($className);
        $genericTypes = self::$cache['generic_types'][$className.$runtime['original_type']] ??= $this->genericTypes($className, $runtime['original_type']);

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $cacheKey = $className.$reflectionProperty->getName();
            $metadata = (self::$cache['metadata'][$cacheKey] ??= ($this->propertyMetadata($reflectionProperty) ?? []));

            if (!$reflectionProperty->isPublic()) {
                continue;
            }

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

                $result[$name] = new PropertyMetadata($type, sprintf('%s->%s', $runtime['accessor'], $reflectionProperty->getName()));

                continue;
            }

            $cacheKey .= json_encode($formatter);
            $formatterReflection = self::$cache['formatter_reflection'][$cacheKey] ??= new \ReflectionFunction(\Closure::fromCallable($formatter));

            if (!$formatterReflection->getClosureScopeClass()?->hasMethod($formatterReflection->getName()) || !$formatterReflection->isStatic()) {
                throw new InvalidArgumentException(sprintf('"%s"\'s property formatter must be a static method.', sprintf('%s::$%s', $className, $reflectionProperty->getName())));
            }

            if (($returnType = $formatterReflection->getReturnType()) instanceof \ReflectionNamedType && ('void' === $returnType->getName() || 'never' === $returnType->getName())) {
                throw new InvalidArgumentException(sprintf('"%s"\'s property formatter return type must not be "void" nor "never".', sprintf('%s::$%s', $className, $reflectionProperty->getName())));
            }

            if (null !== ($configurationParameter = $formatterReflection->getParameters()[1] ?? null)) {
                $configurationParameterType = $configurationParameter->getType();

                if (!$configurationParameterType instanceof \ReflectionNamedType || is_subclass_of($configurationParameterType->getName(), Configuration::class)) {
                    throw new InvalidArgumentException(sprintf('Second argument of "%s"\'s property formatter must be an array.', sprintf('%s::$%s', $className, $reflectionProperty->getName())));
                }
            }

            $type = self::$cache['type'][$cacheKey] ??= $this->typeExtractor->extractFromFunctionReturn($formatterReflection);
            if (isset($genericTypes[(string) $type]) && $formatterReflection->getClosureScopeClass()?->getName() !== $className) {
                $type = $this->typeGenericsHelper->replaceGenericTypes($type, $genericTypes);
            }

            $result[$name] = new PropertyMetadata(
                $type,
                sprintf('%s::%s(%s->%s, $configuration)', $formatterReflection->getClosureScopeClass()->getName(), $formatterReflection->getName(), $runtime['accessor'], $reflectionProperty->getName()),
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

            if (SerializeFormatter::class === $attribute->getName()) {
                /** @var SerializeFormatter $attributeInstance */
                $attributeInstance = $attribute->newInstance();
                $metadata['formatter'] = $attributeInstance->formatter;

                continue;
            }
        }

        return $metadata;
    }

    // TODO factorize
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
