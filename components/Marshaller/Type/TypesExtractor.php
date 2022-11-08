<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

final class TypesExtractor
{
    public function __construct(
        private readonly PhpstanTypesExtractor $phpstanTypesExtractor,
        private readonly PhpDocTypesExtractor $phpDocTypesExtractor,
        private readonly ReflectionTypesExtractor $reflectionTypesExtractor,
    ) {
    }

    public function extract(\ReflectionProperty|\ReflectionFunctionAbstract $reflection, \ReflectionClass $declaringClass): Types
    {
        if (null !== $types = $this->phpstanTypesExtractor->extract($reflection, $declaringClass)) {
            return $types;
        }

        if (null !== $types = $this->phpDocTypesExtractor->extract($reflection, $declaringClass)) {
            return $types;
        }

        if (null !== $types = $this->reflectionTypesExtractor->extract($reflection, $declaringClass)) {
            return $types;
        }

        throw new \RuntimeException('Cannot find type'); // TODO better message
    }
}
