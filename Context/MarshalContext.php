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

    public function withType(string $type): self
    {
        return new self(['type' => $type] + $this->options);
    }

    public function withJsonEncodeFlags(int $flags): self
    {
        return new self(['json_encode_flags' => $flags] + $this->options);
    }

    /**
     * @param array<string, string> $unionSelector
     */
    public function withUnionSelector(array $unionSelector): self
    {
        return new self(['union_selector' => $unionSelector] + $this->options);
    }

    /**
     * @param callable(string, string, array<string, mixed>): array{type: string, accessor: string, context: array<string, mixed>} $hook
     * @param class-string|null                                                                                                    $className
     */
    public function withObjectHook(callable $hook, string $className = null): self
    {
        $hookName = $className ?? 'object';

        return $this->withHook($hookName, $hook);
    }

    /**
     * @param callable(\ReflectionProperty, string, array<string, mixed>): array{name: string, type: string, accessor: string, context: array<string, mixed>} $hook
     * @param class-string|null                                                                                                                               $className
     */
    public function withPropertyHook(callable $hook, string $className = null, string $propertyName = null): self
    {
        $hookName = null !== $className && null !== $propertyName ? sprintf('%s::$%s', $className, $propertyName) : 'property';

        return $this->withHook($hookName, $hook);
    }

    public function withHook(string $hookName, callable $hook): self
    {
        $hooks = $this->options['hooks'] ?? [];
        $hooks[$hookName] = $hook;

        return new self(['hooks' => $hooks] + $this->options);
    }
}
