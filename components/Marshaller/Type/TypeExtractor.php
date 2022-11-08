<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

final class TypeExtractor
{
    public function __construct(
        private readonly PhpstanTypeExtractor $phpstanTypeExtractor,
        private readonly PhpDocTypeExtractor $phpDocTypeExtractor,
        private readonly ReflectionTypeExtractor $reflectionTypeExtractor,
    ) {
    }

    public function extract(\ReflectionProperty|\ReflectionFunctionAbstract $reflection, \ReflectionClass $declaringClass): Type
    {
        if (null !== $type = $this->phpstanTypeExtractor->extract($reflection, $declaringClass)) {
            return $type;
        }

        if (null !== $type = $this->phpDocTypeExtractor->extract($reflection, $declaringClass)) {
            return $type;
        }

        if (null !== $type = $this->reflectionTypeExtractor->extract($reflection, $declaringClass)) {
            return $type;
        }

        throw new \RuntimeException('Cannot find type'); // TODO better message
    }
}
