<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Option;

use Symfony\Component\Marshaller\Context\OptionDeclinationInterface;
use Symfony\Component\Marshaller\Context\OptionInterface;
use Symfony\Component\Marshaller\Metadata\Attribute\GroupsAttribute;
use Symfony\Component\Marshaller\Metadata\ClassMetadata;

final class GroupsOptionDeclination implements OptionDeclinationInterface
{
    /**
     * @return list<string>
     */
    public static function resolve(ClassMetadata $classMetadata): array
    {
        $groups = [];
        foreach ($classMetadata->properties as $propertyMetadata) {
            if (!$propertyMetadata->attributes->has(GroupsAttribute::class)) {
                continue;
            }

            array_push($groups, ...$propertyMetadata->attributes->get(GroupsAttribute::class)->groups);
        }

        return array_unique($groups);
    }

    public static function createOption(array $values): OptionInterface
    {
        return new GroupsOption($values);
    }
}
