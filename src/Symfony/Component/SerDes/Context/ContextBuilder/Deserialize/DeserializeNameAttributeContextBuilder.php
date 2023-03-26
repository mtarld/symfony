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

use Symfony\Component\SerDes\Attribute\Name;
use Symfony\Component\SerDes\Context\ContextBuilder\DeserializeContextBuilderInterface;
use Symfony\Component\SerDes\SerializableResolverInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class DeserializeNameAttributeContextBuilder implements DeserializeContextBuilderInterface
{
    /**
     * @var array<class-string, array<string, string>>|null
     */
    private static ?array $cache = null;

    public function __construct(
        private readonly SerializableResolverInterface $serializableResolver,
    ) {
    }

    public function build(array $context): array
    {
        if (null === self::$cache) {
            $propertyNames = [];

            foreach ($this->serializableResolver->resolve() as $className => $_) {
                $propertyNames += $this->propertyNames($className);
            }

            self::$cache = $propertyNames;
        }

        $context['_symfony']['deserialize']['property_name'] = self::$cache;

        return $context;
    }

    /**
     * @param class-string $className
     *
     * @return array<class-string, array<string, string>>
     */
    private function propertyNames(string $className): array
    {
        $propertyNames = [];

        foreach ((new \ReflectionClass($className))->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (Name::class !== $attribute->getName()) {
                    continue;
                }

                /** @var Name $attributeInstance */
                $attributeInstance = $attribute->newInstance();

                $propertyNames[$property->getDeclaringClass()->getName()][$attributeInstance->name] = $property->getName();

                break;
            }
        }

        return $propertyNames;
    }
}
