<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Context\ContextBuilder;

use Symfony\Component\SerDes\Attribute\Formatter;
use Symfony\Component\SerDes\Context\ContextBuilderInterface;
use Symfony\Component\SerDes\SerializableResolverInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 6.3
 */
final class FormatterAttributeContextBuilder implements ContextBuilderInterface
{
    public function __construct(
        private readonly SerializableResolverInterface $serializableResolver,
    ) {
    }

    public function buildSerializeContext(array $context, bool $willGenerateTemplate): array
    {
        if (!$willGenerateTemplate) {
            return $context;
        }

        return $this->addPropertyFormattersToContext($context);
    }

    public function buildDeserializeContext(array $context): array
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
        foreach ($this->serializableResolver->resolve() as $className => $_) {
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

                if (null !== $attributeInstance->onSerialize) {
                    $context['_symfony']['property_formatter'][$propertyIdentifier]['serialize'] = $attributeInstance->onSerialize;
                }

                if (null !== $attributeInstance->onDeserialize) {
                    $context['_symfony']['property_formatter'][$propertyIdentifier]['deserialize'] = $attributeInstance->onDeserialize;
                }

                break;
            }
        }

        return $context;
    }
}
