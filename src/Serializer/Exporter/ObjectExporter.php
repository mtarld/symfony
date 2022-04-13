<?php

namespace App\Serializer\Exporter;

use App\Serializer\Encoder\Encoder;
use App\Serializer\Extractor\ObjectPropertyListExtractorInterface;
use App\Serializer\Output\Output;
use App\Serializer\Serializer;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final class ObjectExporter implements Exporter
{
    public function __construct(
        private ObjectPropertyListExtractorInterface $propertyListExtractor,
        private PropertyAccessorInterface $accessor,
    ) {
    }

    public function export(mixed $value, string $type, Serializer $serializer, Encoder $encoder): Output
    {
        $generator = function () use ($value): \Generator {
            foreach ($this->propertyListExtractor->getProperties($value) as $property) {
                yield $property => $this->accessor->getValue($value, $property);
            }
        };

        $encoder->encodeDict($generator, $serializer);

        return $encoder->getOutput();
    }

    public function supports(mixed $value, string $type): bool
    {
        return 'object' === $type;
    }
}
