<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY)]
final class Groups
{
    /**
     * @param list<string>|string $groups
     */
    public function __construct(
        public readonly string|array $groups,
    ) {
    }
}
