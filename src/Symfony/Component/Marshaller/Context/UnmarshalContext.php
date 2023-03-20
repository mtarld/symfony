<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context;

use Symfony\Component\Marshaller\Hook\Unmarshal\ObjectHookInterface;
use Symfony\Component\Marshaller\Hook\Unmarshal\PropertyHookInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 6.4
 */
class UnmarshalContext implements ContextInterface
{
    /**
     * @param array<string, mixed> $options
     */
    final public function __construct(
        protected readonly array $options = [],
    ) {
    }

    public function toArray(): array
    {
        return $this->options;
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

    /**
     * @param ObjectHookInterface|callable(string, array<string, mixed>): array{type: string, context: array<string, mixed>} $hook
     * @param class-string|null                                                                                              $className
     */
    public function withObjectHook(ObjectHookInterface|callable $hook, string $className = null): self
    {
        $hookName = $className ?? 'object';

        return $this->withHook($hookName, $hook);
    }

    /**
     * @param PropertyHookInterface|callable(\ReflectionClass<object>, string, callable(): mixed, array<string, mixed>): array{name?: string, value?: callable(): mixed, context?: array<string, mixed>} $hook
     * @param class-string|null                                                                                                                                                                          $className
     */
    public function withPropertyHook(PropertyHookInterface|callable $hook, string $className = null, string $propertyName = null): self
    {
        $hookName = null !== $className && null !== $propertyName ? sprintf('%s::$%s', $className, $propertyName) : 'property';

        return $this->withHook($hookName, $hook);
    }

    public function withEagerReading(): self
    {
        return new self(['lazy_reading' => false] + $this->options);
    }

    public function withLazyReading(): self
    {
        return new self(['lazy_reading' => true] + $this->options);
    }

    public function withEagerInstantiation(): self
    {
        return new self(['instantiator' => 'eager'] + $this->options);
    }

    public function withLazyInstantiation(): self
    {
        return new self(['instantiator' => 'lazy'] + $this->options);
    }

    /**
     * @template T of object
     *
     * @param callable(\ReflectionClass<T>, array<string, mixed>, array<string, mixed>): T $instantiator
     */
    public function withCustomInstantiator(callable $instantiator): self
    {
        return new self(['instantiator' => $instantiator] + $this->options);
    }

    private function withHook(string $hookName, callable $hook): self
    {
        $hooks = $this->options['hooks'] ?? [];
        $hooks['unmarshal'][$hookName] = $hook;

        return new self(['hooks' => $hooks] + $this->options);
    }
}
