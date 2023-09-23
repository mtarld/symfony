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
final readonly class ChainTypeResolver implements TypeResolverInterface
{
    /**
     * @param iterable<TypeResolverInterface>
     */
    public function __construct(
        private iterable $typeResolvers,
    ) {
    }

    public function resolve(mixed $subject): Type
    {
        foreach ($this->typeResolvers as $typeResolver) {
            try {
                return $typeResolver->resolve($subject);
            } catch (UnsupportedException) {
            }
        }

        throw new UnsupportedException('Cannot resolve type.');
    }
}
