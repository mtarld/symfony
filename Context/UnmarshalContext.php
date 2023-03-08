<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context;

class UnmarshalContext implements ContextInterface
{
    use ContextTrait;

    /**
     * @param array<string, mixed> $options
     */
    final public function __construct(
        protected readonly array $options = [],
    ) {
    }

    public function withCollectErrors(bool $collectErrors = true): static
    {
        return $this->with('collect_errors', $collectErrors);
    }

    public function withJsonDecodeFlags(int $flags): static
    {
        return $this->with('json_decode_flags', $flags);
    }

    /**
     * @param array<string, string> $unionSelector
     */
    public function withUnionSelector(array $unionSelector): static
    {
        return $this->with('union_selector', $unionSelector);
    }

    /**
     * @param callable(string, array<string, mixed>): array{type: string, context: array<string, mixed>} $hook
     * @param class-string|null                                                                          $className
     */
    public function withObjectHook(callable $hook, string $className = null): static
    {
        $hookName = $className ?? 'object';

        return $this->withHook($hookName, $hook);
    }

    /**
     * @param callable(\ReflectionClass<object>, string, callable(): mixed, array<string, mixed>): array{name?: string, value?: callable(): mixed, context?: array<string, mixed>} $hook
     * @param class-string|null                                                                                                                                                    $className
     */
    public function withPropertyHook(callable $hook, string $className = null, string $propertyName = null): static
    {
        $hookName = null !== $className && null !== $propertyName ? sprintf('%s::$%s', $className, $propertyName) : 'property';

        return $this->withHook($hookName, $hook);
    }

    public function withHook(string $hookName, callable $hook): static
    {
        $hooks = $this->options['hooks'] ?? [];
        $hooks[$hookName] = $hook;

        return $this->with('hooks', $hooks);
    }

    public function withEagerReading(): static
    {
        return $this->with('lazy_reading', false);
    }

    public function withLazyReading(): static
    {
        return $this->with('lazy_reading', true);
    }

    public function withEagerInstantiation(): static
    {
        return $this->with('instantiator', 'eager');
    }

    public function withLazyInstantiation(): static
    {
        return $this->with('instantiator', 'lazy');
    }

    /**
     * @template T of object
     *
     * @param callable(\ReflectionClass<T>, array<string, mixed>, array<string, mixed>): T $instantiator
     */
    public function withCustomInstantiator(callable $instantiator): static
    {
        return $this->with('instantiator', $instantiator);
    }
}
