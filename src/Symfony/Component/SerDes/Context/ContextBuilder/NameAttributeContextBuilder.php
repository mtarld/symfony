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

use Symfony\Component\SerDes\Attribute\Name;
use Symfony\Component\SerDes\Context\ContextBuilderInterface;
use Symfony\Component\SerDes\SerializableResolverInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class NameAttributeContextBuilder implements ContextBuilderInterface
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

        return $this->addPropertyNamesToContext($context);
    }

    public function buildDeserializeContext(array $context): array
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
        foreach ($this->serializableResolver->resolve() as $className => $_) {
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
