<?php

namespace App\Serializer\Exporter;

use App\Serializer\Extractor\ObjectPropertyListExtractorInterface;
use App\Serializer\Output\Output;
use App\Serializer\Serializable;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final class ObjectExporter implements Exporter
{
    use EncoderAwareTrait;

    public function __construct(
        private ObjectPropertyListExtractorInterface $propertyListExtractor,
        private PropertyAccessorInterface $accessor,
    ) {
    }

    public function export(mixed $value, string $type): Output
    {
        $generator = $value instanceof Serializable
            ? fn () => $value->normalize()
            : function () use ($value): \Generator {
                foreach ($this->propertyListExtractor->getProperties($value) as $property) {
                    yield $property => $this->accessor->getValue($value, $property);
                }
            };

        $this->encoder->encodeDict($generator);

        return $this->encoder->getOutput();
    }

    public function supports(mixed $value, string $type): bool
    {
        return 'object' === $type;
    }
}
