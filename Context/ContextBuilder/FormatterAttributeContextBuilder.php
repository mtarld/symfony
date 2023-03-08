<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context\ContextBuilder;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Marshaller\Attribute\Formatter;
use Symfony\Component\Marshaller\Context\ContextBuilderInterface;
use Symfony\Component\Marshaller\MarshallableResolverInterface;
use Symfony\Component\Marshaller\Util\CachedTrait;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
// TODO cached one with decorator
final class FormatterAttributeContextBuilder implements ContextBuilderInterface
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

        return $this->addMarshallablePropertyFormatters($context);
    }

    public function buildUnmarshalContext(array $context): array
    {
        return $this->addMarshallablePropertyFormatters($context);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function addMarshallablePropertyFormatters(array $context): array
    {
        $cachedContext = $this->getCached('marshaller.context.formatter_attribute', function () use ($context) {
            foreach ($this->marshallableResolver->resolve() as $className => $_) {
                $context = $this->addPropertyFormatters($className, $context);
            }

            return $context;
        });

        if (isset($cachedContext['_symfony']['marshal']['property_formatter'])) {
            $context['_symfony']['marshal']['property_formatter'] = $cachedContext['_symfony']['marshal']['property_formatter'];
        }

        if (isset($cachedContext['_symfony']['unmarshal']['property_formatter'])) {
            $context['_symfony']['unmarshal']['property_formatter'] = $cachedContext['_symfony']['unmarshal']['property_formatter'];
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
                    $context['_symfony']['marshal']['property_formatter'][$propertyIdentifier] = $attributeInstance->marshal;
                }

                if (null !== $attributeInstance->unmarshal) {
                    $context['_symfony']['unmarshal']['property_formatter'][$propertyIdentifier] = $attributeInstance->unmarshal;
                }

                break;
            }
        }

        return $context;
    }
}
