<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Hook;

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
    private readonly TypeGenericsHelper $typeGenericsHelper;

    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
        $this->typeGenericsHelper = new TypeGenericsHelper();
    }

    public function __invoke(Type $type, string $accessor, array $properties, array $context): array
    {
        $className = $type->className();
        $context = $this->addGenericParameterTypes($type, $context);

        $properties = array_filter($properties, fn (string $name): bool => $this->propertyHasMatchingGroup($className, $name, $context), \ARRAY_FILTER_USE_KEY);

        array_walk($properties, function (array &$property, string $name) use ($className, $context): void {
            $formatter = $this->propertyFormatter($className, $name, $context);

            $property['name'] = $this->propertyName($className, $name, $context);
            $property['type'] = $this->propertyType($className, $name, $formatter, $context);
            $property['accessor'] = $this->propertyAccessor($className, $name, $formatter, $property['accessor'], $context);
        });

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

    /**
     * @param class-string         $className
     * @param array<string, mixed> $context
     */
    private function propertyHasMatchingGroup(string $className, string $name, array $context): bool
    {
        if (!isset($context['groups'])) {
            return true;
        }

        foreach ($context['groups'] as $group) {
            if (isset($context['_symfony']['serialize']['property_groups'][$className][$name][$group])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param class-string         $className
     * @param array<string, mixed> $context
     */
    private function propertyName(string $className, string $name, array $context): string
    {
        if (isset($context['_symfony']['serialize']['property_name'][$className][$name])) {
            return $context['_symfony']['serialize']['property_name'][$className][$name];
        }

        return $name;
    }

    /**
     * @param class-string         $className
     * @param array<string, mixed> $context
     */
    private function propertyFormatter(string $className, string $name, array $context): ?\ReflectionFunction
    {
        if (isset($context['_symfony']['serialize']['property_formatter'][$className][$name])) {
            return new \ReflectionFunction(\Closure::fromCallable($context['_symfony']['serialize']['property_formatter'][$className][$name]));
        }

        return null;
    }

    /**
     * @param class-string         $className
     * @param array<string, mixed> $context
     */
    private function propertyType(string $className, string $name, ?\ReflectionFunction $formatter, array $context): Type
    {
        $type = null === $formatter
            ? $this->typeExtractor->extractFromProperty(new \ReflectionProperty($className, $name))
            : $this->typeExtractor->extractFromFunctionReturn($formatter);

        // if method doesn't belong to the property class, ignore generic search
        if (null !== $formatter && $formatter->getClosureScopeClass()?->getName() !== $className) {
            return $type;
        }

        if ([] !== ($genericTypes = $context['_symfony']['generic_parameter_types'][$className] ?? [])) {
            $type = $this->typeGenericsHelper->replaceGenericTypes($type, $genericTypes);
        }

        return $type;
    }

    /**
     * @param class-string         $className
     * @param array<string, mixed> $context
     */
    private function propertyAccessor(string $className, string $name, ?\ReflectionFunction $formatter, string $accessor, array $context): string
    {
        if (null === $formatter) {
            return $accessor;
        }

        if (!$formatter->getClosureScopeClass()?->hasMethod($formatter->getName()) || !$formatter->isStatic()) {
            throw new InvalidArgumentException(sprintf('Property formatter "%s" must be a static method.', sprintf('%s::$%s', $className, $name)));
        }

        if (($returnType = $formatter->getReturnType()) instanceof \ReflectionNamedType && ('void' === $returnType->getName() || 'never' === $returnType->getName())) {
            throw new InvalidArgumentException(sprintf('Return type of property formatter "%s" must not be "void" nor "never".', sprintf('%s::$%s', $className, $name)));
        }

        if (null !== ($contextParameter = $formatter->getParameters()[1] ?? null)) {
            $contextParameterType = $contextParameter->getType();

            if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
                throw new InvalidArgumentException(sprintf('Second argument of property formatter "%s" must be an array.', sprintf('%s::$%s', $className, $name)));
            }
        }

        return sprintf('%s::%s(%s, $context)', $formatter->getClosureScopeClass()->getName(), $formatter->getName(), $accessor);
    }
}
