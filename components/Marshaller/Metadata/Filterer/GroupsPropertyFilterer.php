<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Metadata\Filterer;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\GroupsOption;
use Symfony\Component\Marshaller\Metadata\Attribute\GroupsAttribute;
use Symfony\Component\Marshaller\Metadata\PropertyMetadata;

final class GroupsPropertyFilterer implements PropertyFiltererInterface
{
    public function __construct(
        private readonly PropertyFiltererInterface $inner,
    ) {
    }

    public function filter(array $properties, Context $context): array
    {
        if ($context->has(GroupsOption::class)) {
            $properties = $this->filterByGroups($properties, $context->get(GroupsOption::class)->groups);
        }

        return $this->inner->filter($properties, $context);
    }

    /**
     * @param list<PropertyMetadata> $properties
     * @param list<string>           $groups
     *
     * @return list<PropertyMetadata>
     */
    private function filterByGroups(array $properties, array $contextGroups): array
    {
        foreach ($properties as $k => $property) {
            if ($this->propertyBelongsToNoGroup($property) || $this->propertyMatchesContextGroups($property, $contextGroups)) {
                continue;
            }

            unset($properties[$k]);
        }

        return array_values($properties);
    }

    private function propertyBelongsToNoGroup(PropertyMetadata $property): bool
    {
        return false === $property->attributes->has(GroupsAttribute::class);
    }

    private function propertyMatchesContextGroups(PropertyMetadata $property, array $contextGroups): bool
    {
        $propertyGroups = $property->attributes->get(GroupsAttribute::class)->groups;

        return (bool) array_intersect($contextGroups, $propertyGroups);
    }
}
