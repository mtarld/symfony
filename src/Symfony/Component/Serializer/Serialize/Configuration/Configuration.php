<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Configuration;

use Symfony\Component\Serializer\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
class Configuration
{
    protected ?Type $type = null;

    /**
     * @var list<string> $groups
     */
    protected array $groups = [];

    protected bool $forceGenerateTemplate = false;

    protected JsonConfiguration $jsonConfiguration;
    protected CsvConfiguration $csvConfiguration;

    public function __construct() 
    {
        $this->jsonConfiguration = new JsonConfiguration();
        $this->csvConfiguration = new CsvConfiguration();
    }

    public function type(): ?Type
    {
        return $this->type;
    }

    public function withType(Type|string $type): static
    {
        if (\is_string($type)) {
            $type = Type::createFromString($type);
        }

        $clone = clone $this;
        $clone->type = $type;

        return $clone;
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

    public function csv(): CsvConfiguration
    {
        return $this->csvConfiguration;
    }

    public function withCsvConfiguration(CsvConfiguration $configuration): static
    {
        $clone = clone $this;
        $clone->csvConfiguration = $configuration;

        return $clone;
    }
}
