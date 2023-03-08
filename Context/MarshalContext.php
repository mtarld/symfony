<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context;

class MarshalContext implements ContextInterface
{
    use ContextTrait;

    /**
     * @param array<string, mixed> $options
     */
    final public function __construct(
        protected readonly array $options = [],
    ) {
    }

    public function withType(string $type): static
    {
        return $this->with('type', $type);
    }

    public function withJsonEncodeFlags(int $flags): static
    {
        return $this->with('json_encode_flags', $flags);
    }

    /**
     * @param array<string, string> $unionSelector
     */
    public function withUnionSelector(array $unionSelector): static
    {
        return $this->with('union_selector', $unionSelector);
    }

    /**
     * @param callable(string, string, array<string, mixed>): array{type: string, accessor: string, context: array<string, mixed>} $hook
     * @param class-string|null                                                                                                    $className
     */
    public function withObjectHook(callable $hook, string $className = null): static
    {
        $hookName = $className ?? 'object';

        return $this->withHook($hookName, $hook);
    }

    /**
     * @param callable(\ReflectionProperty, string, array<string, mixed>): array{name: string, type: string, accessor: string, context: array<string, mixed>} $hook
     * @param class-string|null                                                                                                                               $className
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
}
