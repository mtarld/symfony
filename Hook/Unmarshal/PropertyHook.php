<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Hook\Unmarshal;

use Symfony\Component\Marshaller\Type\TypeExtractorInterface;
use Symfony\Component\Marshaller\Type\TypeHelper;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class PropertyHook
{
    private readonly TypeHelper $typeHelper;

    /**
     * @var array<string, string>
     */
    private static array $valueTypesCache = [];

    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
        $this->typeHelper = new TypeHelper();
    }

    /**
     * @param \ReflectionClass<object>                      $class
     * @param callable(string, array<string, mixed>): mixed $value
     * @param array<string, mixed>                          $context
     *
     * @return array{name?: string, value?: callable(): mixed, context?: array<string, mixed>}
     */
    public function __invoke(\ReflectionClass $class, string $key, callable $value, array $context): array
    {
        $propertyClass = $class->getName();
        $propertyName = $context['_symfony']['unmarshal']['property_name'][$propertyClass][$key] ?? $key;
        $cacheKey = $propertyIdentifier = $propertyClass.'::$'.$propertyName;

        if (!$class->hasProperty($propertyName)) {
            return [];
        }

        if (!isset($context['_symfony']['unmarshal']['property_formatter'][$propertyIdentifier])) {
            $valueType = self::$valueTypesCache[$cacheKey] = self::$valueTypesCache[$cacheKey] ?? $this->typeExtractor->extractFromProperty(new \ReflectionProperty($propertyClass, $propertyName));

            if (isset($context['_symfony']['unmarshal']['generic_parameter_types'][$propertyClass])) {
                $valueType = $this->typeHelper->replaceGenericTypes($valueType, $context['_symfony']['unmarshal']['generic_parameter_types'][$propertyClass]);
            }

            return [
                'name' => $propertyName,
                'value' => fn () => $value($valueType, $context),
            ];
        }

        $cacheKey .= ($propertyFormatterHash = json_encode($context['_symfony']['unmarshal']['property_formatter'][$propertyIdentifier]));

        $propertyFormatter = \Closure::fromCallable($context['_symfony']['unmarshal']['property_formatter'][$propertyIdentifier]);
        $propertyFormatterReflection = new \ReflectionFunction($propertyFormatter);

        $valueType = self::$valueTypesCache[$cacheKey] = self::$valueTypesCache[$cacheKey] ?? $this->typeExtractor->extractFromFunctionParameter($propertyFormatterReflection->getParameters()[0]);

        if (
            isset($context['_symfony']['unmarshal']['generic_parameter_types'][$propertyClass])
            && $propertyFormatterReflection->getClosureScopeClass()?->getName() === $propertyClass
        ) {
            $valueType = $this->typeHelper->replaceGenericTypes($valueType, $context['_symfony']['unmarshal']['generic_parameter_types'][$propertyClass]);
        }

        return [
            'name' => $propertyName,
            'value' => fn () => $propertyFormatter($value($valueType, $context), $context),
        ];
    }
}
