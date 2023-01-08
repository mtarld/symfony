<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context\Option;

use Symfony\Component\Marshaller\Exception\InvalidArgumentException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class HookOption
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
            if (!\is_callable($hook)) {
                throw new InvalidArgumentException(sprintf('Hook "%s" is an invalid callable.', $hookName));
            }

            $closures[$hookName] = \Closure::fromCallable($hook);
        }

        $this->hooks = $closures;
    }
}
