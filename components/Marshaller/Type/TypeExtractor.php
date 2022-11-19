<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

final class TypeExtractor
{
    public function __construct(
        private readonly ReflectionTypeExtractor $reflectionExtractor,
        private readonly PhpstanTypeExtractor $phpstanExtractor,
    ) {
    }

    public function extractFromProperty(\ReflectionProperty $property): string
    {
        return $this->phpstanExtractor->extractFromProperty($property) ?? $this->reflectionExtractor->extractFromProperty($property);
    }

    public function extractFromReturnType(\ReflectionFunction $function): string
    {
        return $this->phpstanExtractor->extractFromReturnType($function) ?? $this->reflectionExtractor->extractFromReturnType($function);
    }
}
