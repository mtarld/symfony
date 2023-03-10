<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context\ContextBuilder;

use Symfony\Component\Marshaller\Attribute\Formatter;
use Symfony\Component\Marshaller\Context\ContextBuilderInterface;
use Symfony\Component\Marshaller\MarshallableResolverInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class FormatterAttributeContextBuilder implements ContextBuilderInterface
{
    public function __construct(
        private readonly MarshallableResolverInterface $marshallableResolver,
    ) {
    }

    public function buildMarshalContext(array $context, bool $willGenerateTemplate): array
    {
        if (!$willGenerateTemplate) {
            return $context;
        }

        return $this->addPropertyFormattersToContext($context);
    }

    public function buildUnmarshalContext(array $context): array
    {
        return $this->addPropertyFormattersToContext($context);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function addPropertyFormattersToContext(array $context): array
    {
        foreach ($this->marshallableResolver->resolve() as $className => $_) {
            $context = $this->addPropertyFormatters($className, $context);
        }

        return $context;
    }

    /**
     * @param class-string         $className
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function addPropertyFormatters(string $className, array $context): array
    {
        foreach ((new \ReflectionClass($className))->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (Formatter::class !== $attribute->getName()) {
                    continue;
                }

                $propertyIdentifier = sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName());

                /** @var Formatter $attributeInstance */
                $attributeInstance = $attribute->newInstance();

                if (null !== $attributeInstance->marshal) {
                    $context['_symfony']['property_formatter'][$propertyIdentifier]['marshal'] = $attributeInstance->marshal;
                }

                if (null !== $attributeInstance->unmarshal) {
                    $context['_symfony']['property_formatter'][$propertyIdentifier]['unmarshal'] = $attributeInstance->unmarshal;
                }

                break;
            }
        }

        return $context;
    }
}
