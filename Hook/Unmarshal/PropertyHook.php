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

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class PropertyHook
{
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
        $propertyName = $context['symfony']['unmarshal']['property_name'][$class->getName()][$key] ?? $key;
        $propertyIdentifier = sprintf('%s::$%s', $class->getName(), $propertyName);
        $propertyFormatter = $context['symfony']['unmarshal']['property_formatter'][$propertyIdentifier] ?? null;

        if (null !== $propertyFormatter) {
            $propertyFormatterReflection = new \ReflectionFunction($propertyFormatter);
            $this->validateFormatter($propertyFormatterReflection, $propertyIdentifier);

            $valueType = $this->typeExtractor->extractFromFunctionParameter($propertyFormatterReflection->getParameters()[0]);
        }

        $valueType ??= $this->typeExtractor->extractFromProperty(new \ReflectionProperty($object, $propertyName));

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
