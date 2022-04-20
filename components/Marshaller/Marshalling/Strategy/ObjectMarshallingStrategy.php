<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Marshalling\Strategy;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Marshaller\Encoder\EncoderInterface;
use Symfony\Component\Marshaller\Extractor\ObjectPropertyListExtractorInterface;

final class ObjectMarshallingStrategy implements MarshallingStrategyInterface
{
    public function __construct(
        private ObjectPropertyListExtractorInterface $propertyListExtractor,
        private PropertyAccessorInterface $accessor,
    ) {
    }

    public function marshal(mixed $value, string $type, EncoderInterface $encoder, \Closure $marshal): void
    {
        $generator = function () use ($value): \Generator {
            foreach ($this->propertyListExtractor->getProperties($value) as $property) {
                yield $property => $this->accessor->getValue($value, $property);
            }
        };

        $encoder->encodeDict($generator(), $marshal);
    }

    public function canMarshal(mixed $value, string $type): bool
    {
        return 'object' === $type;
    }
}
