<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo;

use Symfony\Component\TypeInfo\Exception\LogicException;

/**
 * DO NOT REVIEW, this is part of another upcoming PR (work still in progress)
 */
trait CompositeTypeTrait
{
    /**
     * @param callable(Type): bool $callable
     */
    private function atLeastOneTypeIs(callable $callable): bool
    {
        return \count(array_filter($this->types, $callable)) > 0;
    }

    /**
     * @param callable(Type): bool $callable
     */
    private function everyTypeIs(callable $callable): bool
    {
        foreach ($this->types as $t) {
            if (false === $callable($t)) {
                return false;
            }
        }

        return true;
    }

    private function createUnhandledException(string $method): LogicException
    {
        return new LogicException(sprintf('Cannot call "%s()" on a composite type.', $method));
    }
}
