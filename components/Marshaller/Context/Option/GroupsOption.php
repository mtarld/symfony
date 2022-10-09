<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Option;

use Symfony\Component\Marshaller\Context\OptionInterface;

final class GroupsOption implements OptionInterface
{
    public readonly array $groups;

    public function __construct(
        array|string $groups,
    ) {
        $groups = array_unique((array) $groups);

        if (!$groups) {
            throw new \InvalidArgumentException('TODO');
        }

        foreach ($groups as $group) {
            if (!\is_string($group) || '' === $group) {
                throw new \InvalidArgumentException('TODO');
            }
        }

        $this->groups = $groups;
    }

    public function signature(): string
    {
        $groups = $this->groups;
        sort($groups);

        return serialize($groups);
    }
}
