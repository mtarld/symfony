<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\PropertyConfigurator;

use Symfony\Component\Serializer\Attribute\DeserializeFormatter;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Deserialize\PropertyConfigurator\PropertyConfiguratorInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;
use Symfony\Component\Serializer\Type\TypeGenericsHelper;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class PropertyConfigurator implements PropertyConfiguratorInterface
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

    public function configure(string $className, array $properties, array $context): array
    {
        $result = [];

        $genericTypes = self::$cache['generic_types'][$className.$context['type']] ??= $this->genericTypes($className, $context['type']);

        foreach ($properties as $propertyName => $propertyValue) {
            $cacheKey = $className.$propertyName;
            $reflection = self::$cache['reflection'][$cacheKey] ??= new \ReflectionProperty($className, $propertyName);
            $metadata = self::$cache['metadata'][$cacheKey] ??= $this->propertyMetadata($reflection);

            if (isset($context['groups'])) {
                $matchingGroup = false;
                foreach ($context['groups'] as $group) {
                    if (isset($metadata['groups'][$group])) {
                        $matchingGroup = true;

                        break;
                    }
                }

                if (!$matchingGroup) {
                    continue;
                }
            }

            $propertyName = $metadata['name'] ?? $propertyName;

            if (null === $formatter = $metadata['formatter'] ?? null) {
                $type = (self::$cache['type'][$cacheKey] ??= $this->typeExtractor->extractFromProperty($reflection));
                if (isset($genericTypes[(string) $type])) {
                    $type = $this->typeGenericsHelper->replaceGenericTypes($type, $genericTypes);
                }

                $result[$propertyName] = fn () => $propertyValue($type);

                continue;
            }

            $cacheKey .= json_encode($formatter);

            $type = self::$cache['type'][$cacheKey] ??= $this->typeExtractor->extractFromFunctionParameter((new \ReflectionFunction(\Closure::fromCallable($formatter)))->getParameters()[0]);
            if (isset($genericTypes[(string) $type])) {
                $type = $this->typeGenericsHelper->replaceGenericTypes($type, $genericTypes);
            }

            $result[$propertyName] = fn () => $propertyValue($type);
        }

        return $result;
    }

    /**
     * @return array{groups?: array<string, true>, name?: string, formatter?: callable}
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
     * @param array<string, mixed> $context
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
