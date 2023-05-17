<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Hook\Serialize;

use Symfony\Component\SerDes\Exception\InvalidArgumentException;
use Symfony\Component\SerDes\Type\TypeExtractorInterface;
use Symfony\Component\SerDes\Type\TypeGenericsHelper;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class PropertyHook implements PropertyHookInterface
{
    private TypeGenericsHelper|null $typeGenericsHelper = null;

    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
    }

    public function __invoke(\ReflectionProperty $property, string $accessor, array $context): array
    {
        if (null !== ($groups = $context['groups'] ?? null)) {
            $isGroupMatching = false;
            foreach ($groups as $group) {
                if (isset($context['_symfony']['serialize']['property_groups'][$property->getDeclaringClass()->getName()][$property->getName()][$group])) {
                    $isGroupMatching = true;

                    break;
                }
            }

            if (!$isGroupMatching) {
                return ['accessor' => null];
            }
        }

        $propertyFormatter = isset($context['_symfony']['serialize']['property_formatter'][$className = $property->getDeclaringClass()->getName()][$name = $property->getName()])
            ? new \ReflectionFunction(\Closure::fromCallable($context['_symfony']['serialize']['property_formatter'][$className][$name]))
            : null;

        return [
            'name' => $this->name($property, $className, $name, $context),
            'type' => $this->type($property, $propertyFormatter, $context),
            'accessor' => $this->accessor($className, $name, $propertyFormatter, $accessor, $context),
        ];
    }

    /**
     * @param class-string         $className
     * @param array<string, mixed> $context
     */
    private function name(\ReflectionProperty $property, string $className, string $name, array $context): string
    {
        if (isset($context['_symfony']['serialize']['property_name'][$className][$name])) {
            return $context['_symfony']['serialize']['property_name'][$className][$name];
        }

        return $name;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function type(\ReflectionProperty $property, ?\ReflectionFunction $propertyFormatter, array $context): string
    {
        $propertyClass = $property->getDeclaringClass()->getName();

        $type = null !== $propertyFormatter
            ? $this->typeExtractor->extractFromFunctionReturn($propertyFormatter)
            : $this->typeExtractor->extractFromProperty($property);

        // if method doesn't belong to the property class, ignore generic search
        if (null !== $propertyFormatter && $propertyFormatter->getClosureScopeClass()?->getName() !== $propertyClass) {
            $propertyClass = null;
        }

        if ([] !== ($genericTypes = $context['_symfony']['generic_parameter_types'][$propertyClass] ?? [])) {
            $this->typeGenericsHelper = $this->typeGenericsHelper ?? new TypeGenericsHelper();
            $type = $this->typeGenericsHelper->replaceGenericTypes($type, $genericTypes);
        }

        return $type;
    }

    /**
     * @param class-string         $className
     * @param array<string, mixed> $context
     */
    private function accessor(string $className, string $name, ?\ReflectionFunction $propertyFormatter, string $accessor, array $context): string
    {
        if (null === $propertyFormatter) {
            return $accessor;
        }

        if (!$propertyFormatter->getClosureScopeClass()?->hasMethod($propertyFormatter->getName()) || !$propertyFormatter->isStatic()) {
            throw new InvalidArgumentException(sprintf('Property formatter "%s" must be a static method.', sprintf('%s::$%s', $className, $name)));
        }

        if (($returnType = $propertyFormatter->getReturnType()) instanceof \ReflectionNamedType && ('void' === $returnType->getName() || 'never' === $returnType->getName())) {
            throw new InvalidArgumentException(sprintf('Return type of property formatter "%s" must not be "void" nor "never".', sprintf('%s::$%s', $className, $name)));
        }

        if (null !== ($contextParameter = $propertyFormatter->getParameters()[1] ?? null)) {
            $contextParameterType = $contextParameter->getType();

            if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
                throw new InvalidArgumentException(sprintf('Second argument of property formatter "%s" must be an array.', sprintf('%s::$%s', $className, $name)));
            }
        }

        return sprintf('%s::%s(%s, $context)', $propertyFormatter->getClosureScopeClass()->getName(), $propertyFormatter->getName(), $accessor);
    }
}
