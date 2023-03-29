<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Context;

use Symfony\Component\SerDes\Hook\Serialize\ObjectHookInterface;
use Symfony\Component\SerDes\Hook\Serialize\PropertyHookInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
class SerializeContext implements ContextInterface
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

    public function withForceGenerateTemplate(bool $forceGenerateTemplate = true): self
    {
        return new self(['force_generate_template' => $forceGenerateTemplate] + $this->options);
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
     * @param ObjectHookInterface|callable(string, string, array<string, mixed>): array{type: string, accessor: string, context: array<string, mixed>} $hook
     * @param class-string|null                                                                                                                        $className
     */
    public function withObjectHook(ObjectHookInterface|callable $hook, string $className = null): self
    {
        $hookName = $className ?? 'object';

        return $this->withHook($hookName, $hook);
    }

    /**
     * @param PropertyHookInterface|callable(\ReflectionProperty, string, array<string, mixed>): array{name: string, type: string, accessor: string, context: array<string, mixed>} $hook
     * @param class-string|null                                                                                                                                                     $className
     */
    public function withPropertyHook(PropertyHookInterface|callable $hook, string $className = null, string $propertyName = null): self
    {
        $hookName = null !== $className && null !== $propertyName ? sprintf('%s::$%s', $className, $propertyName) : 'property';

        return $this->withHook($hookName, $hook);
    }

    private function withHook(string $hookName, callable $hook): self
    {
        $hooks = $this->options['hooks'] ?? [];
        $hooks['serialize'][$hookName] = $hook;

        return new self(['hooks' => $hooks] + $this->options);
    }
}
