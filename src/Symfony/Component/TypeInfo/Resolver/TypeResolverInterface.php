<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo\Resolver;

use Symfony\Component\TypeInfo\Exception\UnsupportedException;
use Symfony\Component\TypeInfo\Type;

/**
 * DO NOT REVIEW, this is part of another upcoming PR (work still in progress)
 */
interface TypeResolverInterface
{
    /**
     * Try to resolve a {@see Type} on a $subject.
     * If the resolver cannot resolve the type, it will throw a {@see UnsupportedException}.
     *
     * @throws UnsupportedException
     */
    public function resolve(mixed $subject): Type;
}
