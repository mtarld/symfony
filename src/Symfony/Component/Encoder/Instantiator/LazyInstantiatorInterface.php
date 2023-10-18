<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\Instantiator;

/**
 * Instantiates a new $className object lazily with the given $properties callables.
 *
 * A property callable must return the actual property value when being called.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.1
 */
interface LazyInstantiatorInterface extends InstantiatorInterface
{
    /**
     * @param array<string, callable(): mixed> $properties
     */
    public function instantiate(string $className, array $properties): object;
}
