<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context\ContextBuilder;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Marshaller\Attribute\Name;
use Symfony\Component\Marshaller\Context\ContextBuilderInterface;
use Symfony\Component\Marshaller\MarshallableResolverInterface;
use Symfony\Component\Marshaller\Util\CachedTrait;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
// TODO cached one with decorator
final class NameAttributeContextBuilder implements ContextBuilderInterface
{
    use CachedTrait;

    public function __construct(
        private readonly MarshallableResolverInterface $marshallableResolver,
        private readonly CacheItemPoolInterface|null $cacheItemPool = null,
    ) {
    }

    public function buildMarshalContext(array $context, bool $willGenerateTemplate): array
    {
        if (!$willGenerateTemplate) {
            return $context;
        }

        return $this->addMarshallablePropertyNames($context);
    }

    public function buildUnmarshalContext(array $context): array
    {
        return $this->addMarshallablePropertyNames($context);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function addMarshallablePropertyNames(array $context): array
    {
        $cachedContext = $this->getCached('marshaller.context.name_attribute', function () use ($context) {
            foreach ($this->marshallableResolver->resolve() as $className => $_) {
                $context = $this->addPropertyNames($className, $context);
            }

            return $context;
        });

        if (isset($cachedContext['_symfony']['marshal']['property_name'])) {
            $context['_symfony']['marshal']['property_name'] = $cachedContext['_symfony']['marshal']['property_name'];
        }

        if (isset($cachedContext['_symfony']['unmarshal']['property_name'])) {
            $context['_symfony']['unmarshal']['property_name'] = $cachedContext['_symfony']['unmarshal']['property_name'];
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

                $context['_symfony']['marshal']['property_name'][$propertyIdentifier] = $attributeInstance->name;
                $context['_symfony']['unmarshal']['property_name'][sprintf('%s[%s]', $property->getDeclaringClass()->getName(), $attributeInstance->name)] = $property->getName();

                break;
            }
        }

        return $context;
    }
}
