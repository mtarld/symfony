<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Option;

final class HooksOption
{
    /**
     * @var array<string, \Closure>
     */
    public readonly array $hooks;

    /**
     * @param array<string, callable> $hooks
     */
    public function __construct(array $hooks)
    {
        $closures = [];

        foreach ($hooks as $hookName => $hook) {
            if (!is_callable($hook)) {
                throw new \InvalidArgumentException(sprintf('Hook "%s" of attribute "%s" is an invalid callable.', $hookName, self::class));
            }

            $closures[$hookName] = \Closure::fromCallable($hook);
        }

        $this->hooks = $closures;
    }
}
