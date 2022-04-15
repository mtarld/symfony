<?php

declare(strict_types=1);

namespace Symfony\Component\NewSerializer\Serializer;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\NewSerializer\Encoder\EncoderInterface;
use Symfony\Component\NewSerializer\Output\OutputInterface;
use Symfony\Component\NewSerializer\Extractor\ObjectPropertyListExtractorInterface;

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
