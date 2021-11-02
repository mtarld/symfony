<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Context;

/**
 * Holds contexts needed by serialalization indexed by their FQCN.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class Context
{
    /**
     * @template T of object
     *
     * @var array<class-string<T>, T> $optionsMap
     */
    private array $optionsMap = [];

    public function __construct(...$options)
    {
        $this->addOptions(...$options);
    }

    public function addOptions(object ...$optionsList): self
    {
        foreach ($optionsList as $options) {
            $this->optionsMap[get_class($options)] = $options;
        }

        return $this;
    }

    /**
     * @param class-string $optionsClass
     */
    public function removeOptions(string ...$optionsClassList): self
    {
        foreach ($optionsClassList as $optionsClass) {
            unset($this->optionsMap[$optionsClass]);
        }

        return $this;
    }

    /**
     * @param class-string $optionsClass
     */
    public function hasOptions(string $optionsClass): bool
    {
        return isset($this->optionsMap[$optionsClass]);
    }

    /**
     * @template T of object
     *
     * @param class-string<T>
     *
     * @return T|null
     */
    public function getOptions(string $optionsClass): ?object
    {
        return $this->optionsMap[$optionsClass] ?? null;
    }
}
