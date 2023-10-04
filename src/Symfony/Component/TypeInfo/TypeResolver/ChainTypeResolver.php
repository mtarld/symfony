<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo\TypeResolver;

use Symfony\Component\TypeInfo\Exception\UnsupportedException;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeContext\TypeContext;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final readonly class ChainTypeResolver implements TypeResolverInterface
{
    /**
     * @param iterable<TypeResolverInterface> $typeResolvers
     */
    public function __construct(
        private iterable $typeResolvers,
    ) {
    }

    public function resolve(mixed $subject, TypeContext $typeContext = null): Type
    {
        foreach ($this->typeResolvers as $typeResolver) {
            try {
                return $typeResolver->resolve($subject, $typeContext);
            } catch (UnsupportedException) {
            }
        }

        throw new UnsupportedException('Cannot resolve type.');
    }
}
