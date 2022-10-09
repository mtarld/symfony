<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Metadata\Attribute;

use Symfony\Component\Marshaller\Attribute\Name;

final class NameAttribute
{
    public readonly string $name;

    public function __construct(\ReflectionAttribute $reflection)
    {
        if (Name::class !== $reflection->getName()) {
            throw new \RuntimeException('TODO');
        }

        $this->name = $reflection->getArguments()[0];

        if ('' === $this->name) {
            throw new \InvalidArgumentException(sprintf('Parameter of attribute "%s" must be a non-empty string.', Name::class));
        }
    }
}
