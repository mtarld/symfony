<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Hook\Marshal;

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
     * @param array<string, mixed> $context
     *
     * @return array{name: string, type: string, accessor: string, context: array<string, mixed>}
     */
    public function __invoke(\ReflectionProperty $property, string $accessor, array $context): array
    {
        $propertyIdentifier = sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName());

        $propertyFormatter = isset($context['symfony']['marshal']['property_formatter'][$propertyIdentifier])
            ? new \ReflectionFunction($context['symfony']['marshal']['property_formatter'][$propertyIdentifier])
            : null;

        $context['symfony']['marshal']['current_property_class'] = $property->getDeclaringClass()->getName();

        return [
            'name' => $this->name($property, $propertyIdentifier, $context),
            'type' => $this->type($property, $propertyFormatter, $context),
            'accessor' => $this->accessor($propertyIdentifier, $propertyFormatter, $accessor, $context),
            'context' => $context,
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    private function name(\ReflectionProperty $property, string $propertyIdentifier, array $context): string
    {
        $name = $property->getName();

        if (isset($context['symfony']['marshal']['property_name'][$propertyIdentifier])) {
            $name = $context['symfony']['marshal']['property_name'][$propertyIdentifier];
        }

        return $name;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function type(\ReflectionProperty $property, ?\ReflectionFunction $propertyFormatter, array $context): string
    {
        return null !== $propertyFormatter
            ? $this->typeExtractor->extractFromReturnType($propertyFormatter)
            : $this->typeExtractor->extractFromProperty($property);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function accessor(string $propertyIdentifier, ?\ReflectionFunction $propertyFormatter, string $accessor, array $context): string
    {
        if (null === $propertyFormatter) {
            return $accessor;
        }

        if (!$propertyFormatter->getClosureScopeClass()?->hasMethod($propertyFormatter->getName()) || !$propertyFormatter->isStatic()) {
            throw new InvalidArgumentException(sprintf('Property formatter "%s" must be a static method.', $propertyIdentifier));
        }

        if (($returnType = $propertyFormatter->getReturnType()) instanceof \ReflectionNamedType && ('void' === $returnType->getName() || 'never' === $returnType->getName())) {
            throw new InvalidArgumentException(sprintf('Return type of property formatter "%s" must not be "void" nor "never".', $propertyIdentifier));
        }

        if (null !== ($contextParameter = $propertyFormatter->getParameters()[1] ?? null)) {
            $contextParameterType = $contextParameter->getType();

            if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
                throw new InvalidArgumentException(sprintf('Second argument of property formatter "%s" must be an array.', $propertyIdentifier));
            }
        }

        return sprintf('%s::%s(%s, $context)', $propertyFormatter->getClosureScopeClass()->getName(), $propertyFormatter->getName(), $accessor);
    }
}
