<?php

declare(strict_types=1);

namespace App\Serializer\Exporter;

use App\Serializer\Encoder\EncoderInterface;
use App\Serializer\Extractor\ObjectPropertyListExtractorInterface;
use App\Serializer\Output\OutputInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final class ObjectExporter implements Exporter
{
    public function __construct(
        private ObjectPropertyListExtractorInterface $propertyListExtractor,
        private PropertyAccessorInterface $accessor,
    ) {
    }

    public function serialize(mixed $value, string $type, EncoderInterface $encoder, ChainExporter $chainSerializer): OutputInterface
    {
        $generator = function () use ($value): \Generator {
            foreach ($this->propertyListExtractor->getProperties($value) as $property) {
                yield $property => $this->accessor->getValue($value, $property);
            }
        };

        $encoder->encodeDict($generator, $chainSerializer);

        return $encoder->getOutput();
    }

    public function supports(mixed $value, string $type): bool
    {
        return 'object' === $type;
    }
}
