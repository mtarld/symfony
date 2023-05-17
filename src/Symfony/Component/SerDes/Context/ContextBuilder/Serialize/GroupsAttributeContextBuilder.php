<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Context\ContextBuilder\Serialize;

use Symfony\Component\SerDes\Attribute\Groups;
use Symfony\Component\SerDes\Context\ContextBuilder\SerializeContextBuilderInterface;
use Symfony\Component\SerDes\SerializableResolver\SerializableResolverInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class GroupsAttributeContextBuilder implements SerializeContextBuilderInterface
{
    /**
     * @var array<class-string, array<string, array<string, true>>>|null
     */
    private static ?array $cache = null;

    public function __construct(
        private readonly SerializableResolverInterface $serializableResolver,
    ) {
    }

    public function build(array $context): array
    {
        if (true === ($context['template_exists'] ?? false)) {
            return $context;
        }

        if (null === self::$cache) {
            $propertyGroups = [];

            foreach ($this->serializableResolver->resolve() as $className) {
                $propertyGroups += $this->propertyGroups($className);
            }

            self::$cache = $propertyGroups;
        }

        $context['_symfony']['serialize']['property_groups'] = self::$cache;

        return $context;
    }

    /**
     * @param class-string $className
     *
     * @return array<class-string, array<string, array<string, true>>>
     */
    private function propertyGroups(string $className): array
    {
        $propertyGroups = [];

        foreach ((new \ReflectionClass($className))->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (Groups::class !== $attribute->getName()) {
                    continue;
                }

                /** @var Groups $attributeInstance */
                $attributeInstance = $attribute->newInstance();

                foreach ($attributeInstance->groups as $group) {
                    $propertyGroups[$property->getDeclaringClass()->getName()][$property->getName()][$group] = true;
                }

                break;
            }
        }

        return $propertyGroups;
    }
}
