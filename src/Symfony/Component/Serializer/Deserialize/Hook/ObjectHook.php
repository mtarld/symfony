<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Hook;

use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;
use Symfony\Component\Serializer\Type\TypeGenericsHelper;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class ObjectHook implements ObjectHookInterface
{
    /**
     * @var array{type: array<string, Type>}
     */
    private static array $cache = [
        'type' => [],
    ];

    private readonly TypeGenericsHelper $typeGenericsHelper;

    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
        $this->typeGenericsHelper = new TypeGenericsHelper();
    }

    public function __invoke(Type $type, array $properties, array $context): array
    {
        $className = $type->className();
        $context = $this->addGenericParameterTypes($type, $context);

        foreach ($properties as $name => &$property) {
            if (isset($context['groups'])) {
                $matchingGroup = false;
                foreach ($context['groups'] as $group) {
                    if (isset($context['_symfony']['deserialize']['property_groups'][$className][$name][$group])) {
                        $matchingGroup = true;

                        break;
                    }
                }

                if (!$matchingGroup) {
                    unset($properties[$name]);

                    continue;
                }
            }

            $cacheKey = $className.$name;

            $property['name'] = $context['_symfony']['deserialize']['property_name'][$className][$name] ?? $name;

            if (null === $formatter = $context['_symfony']['deserialize']['property_formatter'][$className][$name] ?? null) {
                $type = (self::$cache['type'][$cacheKey] ??= $this->typeExtractor->extractFromProperty(new \ReflectionProperty($className, $property['name'])));

                if (isset($context['_symfony']['generic_parameter_types'][$className])) {
                    $type = $this->typeGenericsHelper->replaceGenericTypes($type, $context['_symfony']['generic_parameter_types'][$className]);
                }

                $property['value_provider'] = fn (Type $initialType) => $property['value_provider']($type);

                continue;
            }

            $cacheKey .= ($propertyFormatterHash = json_encode($context['_symfony']['deserialize']['property_formatter'][$className][$property['name']]));

            $propertyFormatter = \Closure::fromCallable($context['_symfony']['deserialize']['property_formatter'][$className][$property['name']]);
            $propertyFormatterReflection = new \ReflectionFunction($propertyFormatter);

            $type = (self::$cache['type'][$cacheKey] ??= $this->typeExtractor->extractFromFunctionParameter($propertyFormatterReflection->getParameters()[0]));

            if (isset($context['_symfony']['generic_parameter_types'][$className]) && $propertyFormatterReflection->getClosureScopeClass()?->getName() === $className) {
                $type = $this->typeGenericsHelper->replaceGenericTypes($type, $context['_symfony']['generic_parameter_types'][$className]);
            }

            $property['value_provider'] = fn (Type $initialType) => $propertyFormatter($property['value_provider']($type), $context);
        }

        return [
            'properties' => $properties,
            'context' => $context,
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function addGenericParameterTypes(Type $type, array $context): array
    {
        $className = $type->className();
        $genericParameterTypes = $type->genericParameterTypes();

        $templates = $this->typeExtractor->extractTemplateFromClass(new \ReflectionClass($className));

        if (\count($templates) !== \count($genericParameterTypes)) {
            throw new InvalidArgumentException(sprintf('Given %d generic parameters in "%s", but %d templates are defined in "%s".', \count($genericParameterTypes), (string) $type, \count($templates), $className));
        }

        foreach ($genericParameterTypes as $i => $genericParameterType) {
            $context['_symfony']['generic_parameter_types'][$className][$templates[$i]] = $genericParameterType;
        }

        return $context;
    }
}
