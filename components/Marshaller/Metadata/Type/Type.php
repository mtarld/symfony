<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Metadata\Type;

final class Type
{
    /**
     * @param class-string|null $className
     * @param list<self> $collectionKeyTypes
     * @param list<self> $collectionValueTypes
     */
    public function __construct(
        public readonly string $builtinType,
        public readonly bool $nullable,
        public readonly ?string $className = null,
        public readonly array $collectionKeyTypes = [],
        public readonly array $collectionValueTypes = [],
    ) {
    }
}
