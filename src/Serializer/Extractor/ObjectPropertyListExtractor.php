<?php

declare(strict_types=1);

namespace App\Serializer\Extractor;

use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;

final class ObjectPropertyListExtractor implements ObjectPropertyListExtractorInterface
{
    public function __construct(
        private PropertyInfoExtractorInterface $extractor,
    ) {
    }

    public function getProperties(object $object): array
    {
        return $this->extractor->getProperties($object::class);
    }
}
