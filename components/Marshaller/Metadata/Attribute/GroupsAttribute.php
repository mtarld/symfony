<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Metadata\Attribute;

use Symfony\Component\Marshaller\Attribute\Groups;

final class GroupsAttribute
{
    /**
     * @var list<string>
     */
    public readonly array $groups;

    public function __construct(\ReflectionAttribute $reflection)
    {
        if (Groups::class !== $reflection->getName()) {
            throw new \RuntimeException('TODO');
        }

        $this->groups = array_unique((array) $reflection->getArguments()[0]);

        if (!$this->groups) {
            throw new \InvalidArgumentException(sprintf('Parameter of attribute "%s" cannot be empty.', Groups::class));
        }

        foreach ($this->groups as $group) {
            if (!\is_string($group) || '' === $group) {
                throw new \InvalidArgumentException(sprintf('Parameter of attribute "%s" must be a string or an array of non-empty strings.', Groups::class));
            }
        }
    }
}
