<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Hook\Unmarshal;

use Symfony\Component\Marshaller\Exception\InvalidArgumentException;
use Symfony\Component\Marshaller\Type\TypeExtractorInterface;
use Symfony\Component\Marshaller\Type\TypeHelper;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class PropertyHook
{
    private TypeHelper|null $typeHelper = null;

    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
    }

    /**
     * @param \ReflectionClass<object>                      $class
     * @param callable(string, array<string, mixed>): mixed $value
     * @param array<string, mixed>                          $context
     */
    public function __invoke(\ReflectionClass $class, object $object, string $key, callable $value, array $context): void
    {
        $propertyClass = $class->getName();
        $propertyName = $context['_symfony']['unmarshal']['property_name'][$class->getName()][$key] ?? $key;
        $propertyIdentifier = sprintf('%s::$%s', $class->getName(), $propertyName);
        $propertyFormatter = isset($context['_symfony']['unmarshal']['property_formatter'][$propertyIdentifier])
            ? \Closure::fromCallable($context['_symfony']['unmarshal']['property_formatter'][$propertyIdentifier])
            : null;

        if (null !== $propertyFormatter) {
            $propertyFormatterReflection = new \ReflectionFunction($propertyFormatter);
            $this->validateFormatter($propertyFormatterReflection, $propertyIdentifier);

            $valueType = $this->typeExtractor->extractFromFunctionParameter($propertyFormatterReflection->getParameters()[0]);

            // if method doesn't belong to the property class, ignore generic search
            if ($propertyFormatterReflection->getClosureScopeClass()?->getName() !== $propertyClass) {
                $propertyClass = null;
            }
        }

        $valueType ??= $this->typeExtractor->extractFromProperty(new \ReflectionProperty($object, $propertyName));

        if ([] !== ($genericTypes = $context['_symfony']['unmarshal']['generic_parameter_types'][$propertyClass] ?? [])) {
            $this->typeHelper = $this->typeHelper ?? new TypeHelper();
            $valueType = $this->typeHelper->replaceGenericTypes($valueType, $genericTypes);
        }

        $propertyValue = $value($valueType, $context);

        if (null !== $propertyFormatter) {
            $propertyValue = $propertyFormatter($propertyValue, $context);
        }

        $object->{$propertyName} = $propertyValue;
    }

    private function validateFormatter(\ReflectionFunction $reflection, string $propertyIdentifier): void
    {
        if (!$reflection->getClosureScopeClass()?->hasMethod($reflection->getName()) || !$reflection->isStatic()) {
            throw new InvalidArgumentException(sprintf('Property formatter "%s" must be a static method.', $propertyIdentifier));
        }

        if (($returnType = $reflection->getReturnType()) instanceof \ReflectionNamedType && ('void' === $returnType->getName() || 'never' === $returnType->getName())) {
            throw new InvalidArgumentException(sprintf('Return type of property formatter "%s" must not be "void" nor "never".', $propertyIdentifier));
        }

        if (\count($reflection->getParameters()) < 1) {
            throw new InvalidArgumentException(sprintf('Property formatter "%s" must have at least one argument.', $propertyIdentifier));
        }

        if (null !== ($contextParameter = $reflection->getParameters()[1] ?? null)) {
            $contextParameterType = $contextParameter->getType();

            if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
                throw new InvalidArgumentException(sprintf('Second argument of property formatter "%s" must be an array.', $propertyIdentifier));
            }
        }
    }
}
