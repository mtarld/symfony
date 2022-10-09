<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Metadata;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Metadata\Filterer\PropertyFiltererInterface;

final class ClassMetadataFactory
{
    public function __construct(
        private readonly PropertyMetadataFactory $propertyMetadataFactory,
        private readonly PropertyFiltererInterface $propertyFilterer,
    ) {
    }

    public function forClass(\ReflectionClass $class, Context $context, array $factoryContext = []): ClassMetadata
    {
        $properties = array_map(
            fn (\ReflectionProperty $p): PropertyMetadata => $this->propertyMetadataFactory->forProperty($p, $context, $factoryContext),
            $class->getProperties(),
        );

        return new ClassMetadata(
            $class->getName(),
            $this->propertyFilterer->filter($properties, $context),
        );
    }
}
