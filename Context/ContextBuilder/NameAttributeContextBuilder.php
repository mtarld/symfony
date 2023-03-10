<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context\ContextBuilder;

use Symfony\Component\Marshaller\Attribute\Name;
use Symfony\Component\Marshaller\Context\ContextBuilderInterface;
use Symfony\Component\Marshaller\MarshallableResolverInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class NameAttributeContextBuilder implements ContextBuilderInterface
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

        return $this->addPropertyNamesToContext($context);
    }

    public function buildUnmarshalContext(array $context): array
    {
        return $this->addPropertyNamesToContext($context);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function addPropertyNamesToContext(array $context): array
    {
        foreach ($this->marshallableResolver->resolve() as $className => $_) {
            $context = $this->addPropertyNames($className, $context);
        }

        return $context;
    }

    /**
     * @param class-string         $className
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function addPropertyNames(string $className, array $context): array
    {
        foreach ((new \ReflectionClass($className))->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (Name::class !== $attribute->getName()) {
                    continue;
                }

                /** @var Name $attributeInstance */
                $attributeInstance = $attribute->newInstance();

                $propertyIdentifier = sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName());
                $context['_symfony']['property_name'][$propertyIdentifier] = $attributeInstance->name;

                $keyIdentifier = sprintf('%s[%s]', $property->getDeclaringClass()->getName(), $attributeInstance->name);
                $context['_symfony']['property_name'][$keyIdentifier] = $property->getName();

                break;
            }
        }

        return $context;
    }
}
