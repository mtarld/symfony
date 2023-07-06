<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Configuration;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
class Configuration
{
    /**
     * @var list<string> $groups
     */
    protected array $groups = [];

    protected bool $lazyUnmarshal = false;

    protected JsonConfiguration $jsonConfiguration;

    public function __construct() 
    {
        $this->jsonConfiguration = new JsonConfiguration();
    }

    /**
     * @return list<string>
     */
    public function groups(): array
    {
        return $this->groups;
    }

    /**
     * @param non-empty-string|non-empty-array<int, non-empty-string> $groups
     */
    public function withGroups(array|string $groups): static
    {
        $clone = clone $this;
        $clone->groups = array_values(array_unique((array) $groups));

        return $clone;
    }

    public function lazyUnmarshal(): bool
    {
        return $this->lazyUnmarshal;
    }

    public function withLazyUnmarshal(bool $lazyUnmarshal = true): static
    {
        $clone = clone $this;
        $clone->lazyUnmarshal = $lazyUnmarshal;

        return $clone;
    }

    public function json(): JsonConfiguration
    {
        return $this->jsonConfiguration;
    }

    public function withJsonConfiguration(JsonConfiguration $configuration): static
    {
        $clone = clone $this;
        $clone->jsonConfiguration = $configuration;

        return $clone;
    }
}
