<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Option;

final class PropertyTypeOption
{
    /**
     * @var array<string, array<string, string>
     */
    public readonly array $types;

    /**
     * @param array<string, array<string, string>> $classPropertyTypes
     */
    public function __construct(array $classPropertyTypes)
    {
        $types = [];

        foreach ($classPropertyTypes as $className => $propertyTypes) {
            foreach ($propertyTypes as $propertyName => $type) {
                $types[sprintf('%s::$%s', $className, $propertyName)] = $type;
            }
        }

        $this->types = $types;
    }
}
