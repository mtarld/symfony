<?php

declare(strict_types=1);

namespace App\Serializer\Serializer;

use App\Serializer\Encoder\EncoderInterface;
use App\Serializer\Extractor\ObjectPropertyListExtractorInterface;
use App\Serializer\Output\OutputInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final class ObjectSerializer implements SerializerInterface
{
    public function __construct(
        private ObjectPropertyListExtractorInterface $propertyListExtractor,
        private PropertyAccessorInterface $accessor,
    ) {
    }

    public function serialize(mixed $value, string $type, EncoderInterface $encoder, \Closure $serialize): OutputInterface
    {
        $generator = function () use ($value): \Generator {
            foreach ($this->propertyListExtractor->getProperties($value) as $property) {
                yield $property => $this->accessor->getValue($value, $property);
            }
        };

        $encoder->encodeDict($generator, $serialize);

        return $encoder->getOutput();
    }

    public function supports(mixed $value, string $type): bool
    {
        return 'object' === $type;
    }
}
