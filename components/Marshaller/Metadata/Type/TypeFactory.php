<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Metadata\Type;

use Symfony\Component\PropertyInfo\Type as PropertyInfoType;

final class TypeFactory
{
    public function fromPropertyInfoType(PropertyInfoType $propertyInfoType): Type
    {
        return new Type(
            $propertyInfoType->getBuiltinType(),
            $propertyInfoType->isNullable(),
            $propertyInfoType->getClassName(),
            array_map(fn (PropertyInfoType $t): Type => $this->fromPropertyInfoType($t), $propertyInfoType->getCollectionKeyTypes()),
            array_map(fn (PropertyInfoType $t): Type => $this->fromPropertyInfoType($t), $propertyInfoType->getCollectionValueTypes()),
        );
    }
}
