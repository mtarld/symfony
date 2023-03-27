<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Context\ContextBuilder\Deserialize;

use Symfony\Component\SerDes\Attribute\Formatter;
use Symfony\Component\SerDes\Context\ContextBuilder\DeserializeContextBuilderInterface;
use Symfony\Component\SerDes\SerializableResolver\SerializableResolverInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class DeserializeFormatterAttributeContextBuilder implements DeserializeContextBuilderInterface
{
    /**
     * @var array<class-string, array<string, callable>>|null
     */
    private static ?array $cache = null;

    public function __construct(
        private readonly SerializableResolverInterface $serializableResolver,
    ) {
    }

    public function build(array $context): array
    {
        if (null === self::$cache) {
            $propertyFormatters = [];

            foreach ($this->serializableResolver->resolve() as $className => $_) {
                $propertyFormatters += $this->propertyFormatters($className);
            }

            self::$cache = $propertyFormatters;
        }

        $context['_symfony']['deserialize']['property_formatter'] = self::$cache;

        return $context;
    }

    /**
     * @param class-string $className
     *
     * @return array<class-string, array<string, callable>>
     */
    private function propertyFormatters(string $className): array
    {
        $propertyFormatters = [];

        foreach ((new \ReflectionClass($className))->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (Formatter::class !== $attribute->getName()) {
                    continue;
                }

                /** @var Formatter $attributeInstance */
                $attributeInstance = $attribute->newInstance();

                if (null === $attributeInstance->onDeserialize) {
                    break;
                }

                $propertyFormatters[$property->getDeclaringClass()->getName()][$property->getName()] = $attributeInstance->onDeserialize;

                break;
            }
        }

        return $propertyFormatters;
    }
}
