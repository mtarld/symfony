<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Metadata\Type;

use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;

final class PropertyTypeExtractor
{
    public function __construct(
        private readonly PropertyTypeExtractorInterface $typeExtractor,
        private readonly TypeFactory $typeFactory,
    ) {
    }

    public function extract(string $class, string $property): Type
    {
        // TODO union?
        $type = $this->typeExtractor->getTypes($class, $property)[0] ?? null;
        if (null === $type) {
            throw new \RuntimeException(sprintf('Cannot find any type for property %s::%s', $class, $property));
        }

        return $this->typeFactory->fromPropertyInfoType($type);
    }
}
