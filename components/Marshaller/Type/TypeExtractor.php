<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

final class TypeExtractor
{
    public function __construct(
        private readonly ReflectionTypeExtractor $reflectionTypeExtractor,
        private readonly PhpDocTypeExtractor $phpDocTypeExtractor,
    ) {
    }

    /**
     * @return list<Type>
     */
    public function extract(\ReflectionProperty|\ReflectionFunctionAbstract $reflection): array
    {
        // if (null !== $types = $this->phpDocTypeExtractor->extract($reflection)) {
        //     return $types;
        // }

        if (null !== $types = $this->reflectionTypeExtractor->extract($reflection)) {
            return $types;
        }

        throw new \RuntimeException('Cannot find type'); // TODO better message
    }
}
