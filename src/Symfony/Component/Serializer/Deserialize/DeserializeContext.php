<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize;

use Symfony\Component\Serializer\ContextInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
readonly class DeserializeContext implements ContextInterface
{
    /**
     * @param array<string, mixed> $options
     */
    final public function __construct(
        protected array $options = [],
    ) {
    }

    public function toArray(): array
    {
        return $this->options;
    }

    /**
     * @param non-empty-string|non-empty-array<int, non-empty-string> $groups
     */
    public function withGroups(array|string $groups): self
    {
        return new self(['groups' => array_values(array_unique((array) $groups))] + $this->options);
    }

    public function withCollectErrors(bool $collectErrors = true): self
    {
        return new self(['collect_errors' => $collectErrors] + $this->options);
    }

    public function withJsonDecodeFlags(int $flags): self
    {
        return new self(['json_decode_flags' => $flags] + $this->options);
    }

    /**
     * @param array<string, string> $unionSelector
     */
    public function withUnionSelector(array $unionSelector): self
    {
        return new self(['union_selector' => $unionSelector] + $this->options);
    }

    public function withEagerReading(): self
    {
        return new self(['lazy_reading' => false] + $this->options);
    }

    // TODO lazy_unmarshal?
    public function withLazyReading(): self
    {
        return new self(['lazy_reading' => true] + $this->options);
    }

    public function withEagerInstantiation(): self
    {
        return new self(['lazy_instantiation' => false] + $this->options);
    }

    public function withLazyInstantiation(): self
    {
        return new self(['lazy_instantiation' => true] + $this->options);
    }
}
