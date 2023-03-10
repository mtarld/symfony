<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Attribute\Marshallable;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
interface MarshallableResolverInterface
{
    /**
     * @return iterable<class-string, Marshallable>
     */
    public function resolve(): iterable;
}
