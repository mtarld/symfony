<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Hook\Unmarshal;

use Symfony\Component\Marshaller\Type\TypeExtractorInterface;
use Symfony\Component\Marshaller\Type\TypeGenericsHelper;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 6.4
 */
final class PropertyHook implements PropertyHookInterface
{
    private readonly TypeGenericsHelper $typeGenericsHelper;

    /**
     * @var array{value_type: array<string, string>, class_has_property: array<string, bool>}
     */
    private static array $cache = [
        'value_type' => [],
        'class_has_property' => [],
    ];

    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
        $this->typeGenericsHelper = new TypeGenericsHelper();
    }

    public function __invoke(\ReflectionClass $class, string $key, callable $value, array $context): array
    {
        $propertyClass = $class->getName();

        /** @var string $propertyName */
        $propertyName = $context['_symfony']['property_name'][sprintf('%s[%s]', $propertyClass, $key)] ?? $key;
        $cacheKey = $propertyIdentifier = $propertyClass.'::$'.$propertyName;

        if (!self::$cache['class_has_property'][$cacheKey] = self::$cache['class_has_property'][$cacheKey] ?? $class->hasProperty($propertyName)) {
            return [];
        }

        if (!isset($context['_symfony']['property_formatter'][$propertyIdentifier]['unmarshal'])) {
            $valueType = self::$cache['value_type'][$cacheKey] = self::$cache['value_type'][$cacheKey] ?? $this->typeExtractor->extractFromProperty(new \ReflectionProperty($propertyClass, $propertyName));

            if (isset($context['_symfony']['generic_parameter_types'][$propertyClass])) {
                $valueType = $this->typeGenericsHelper->replaceGenericTypes($valueType, $context['_symfony']['generic_parameter_types'][$propertyClass]);
            }

            return [
                'name' => $propertyName,
                'value_provider' => fn () => $value($valueType, $context),
            ];
        }

        $cacheKey .= ($propertyFormatterHash = json_encode($context['_symfony']['property_formatter'][$propertyIdentifier]['unmarshal']));

        $propertyFormatter = \Closure::fromCallable($context['_symfony']['property_formatter'][$propertyIdentifier]['unmarshal']);
        $propertyFormatterReflection = new \ReflectionFunction($propertyFormatter);

        $valueType = self::$cache['value_type'][$cacheKey] = self::$cache['value_type'][$cacheKey] ?? $this->typeExtractor->extractFromFunctionParameter($propertyFormatterReflection->getParameters()[0]);

        if (
            isset($context['_symfony']['generic_parameter_types'][$propertyClass])
            && $propertyFormatterReflection->getClosureScopeClass()?->getName() === $propertyClass
        ) {
            $valueType = $this->typeGenericsHelper->replaceGenericTypes($valueType, $context['_symfony']['generic_parameter_types'][$propertyClass]);
        }

        return [
            'name' => $propertyName,
            'value_provider' => fn () => $propertyFormatter($value($valueType, $context), $context),
        ];
    }
}
