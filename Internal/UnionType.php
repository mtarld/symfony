<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class UnionType implements \Stringable
{
    /**
     * @param list<Type> $types
     */
    public function __construct(
        public readonly array $types,
    ) {
    }

    public function isNullable(): bool
    {
        return $this->atLeastOneTypeIs(fn (Type $t): bool => $t->isNull());
    }

    /**
     * @param callable(Type): mixed $callable
     */
    public function atLeastOneTypeIs(callable $callable): bool
    {
        foreach ($this->types as $type) {
            if ($callable($type)) {
                return true;
            }
        }

        return false;
    }

    public function __toString(): string
    {
        return implode('|', array_map(fn (Type $t): string => (string) $t, $this->types));
    }
}
