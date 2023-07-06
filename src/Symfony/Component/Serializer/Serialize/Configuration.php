<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize;

use Symfony\Component\Serializer\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
class Configuration
{
    protected ?Type $type;

    /**
     * @var list<string> $groups
     */
    protected array $groups;

    /**
     * @param list<string>|string $groups
     */
    public function __construct(
        Type|string|null $type = null,
        array|string $groups = [],
        protected bool $forceGenerateTemplate = false,
    ) {
        if (\is_string($type)) {
            $type = Type::createFromString($type);
        }

        $this->type = $type;
        $this->groups = (array) $groups;
    }

    public function type(): ?Type
    {
        return $this->type;
    }

    public function withType(Type|string $type): static
    {
        $clone = clone $this;

        if (\is_string($type)) {
            $type = Type::createFromString($type);
        }

        $clone->type = $type;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function groups(): array
    {
        return $this->groups;
    }

    /**
     * @param list<string>|string $groups
     */
    public function withGroups(array|string $groups): static
    {
        $clone = clone $this;
        $clone->groups = (array) $groups;

        return $clone;
    }

    public function forceGenerateTemplate(): bool
    {
        return $this->forceGenerateTemplate;
    }

    public function withForceGenerateTemplate(bool $forceGenerateTemplate): static
    {
        $clone = clone $this;
        $clone->forceGenerateTemplate = $forceGenerateTemplate;

        return $clone;
    }
}
