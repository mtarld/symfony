<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Template\Generator;

use Symfony\Component\Marshaller\Metadata\ValueMetadata;

final class ValueGenerators
{
    /**
     * @param iterable<ValueGeneratorInterface> $valueGenerators
     */
    public function __construct(
        private readonly iterable $valueGenerators,
    ) {
    }

    public function for(ValueMetadata $value): ValueGeneratorInterface
    {
        foreach ($this->valueGenerators as $valueGenerator) {
            if ($valueGenerator->canGenerate($value)) {
                return $valueGenerator;
            }
        }

        throw new \RuntimeException('Cannot find generator');
    }
}
