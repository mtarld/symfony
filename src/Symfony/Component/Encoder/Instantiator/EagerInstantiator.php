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

use Symfony\Component\Encoder\Exception\UnexpectedValueException;

/**
 * Instantiates a new $className eagerly, then set the given properties.
 *
 * The $className class must have a constructor without any parameter
 * and the related properties must be public.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.1
 */
final readonly class EagerInstantiator implements InstantiatorInterface
{
    public function instantiate(string $className, array $properties): object
    {
        $object = new $className();

        foreach ($properties as $name => $value) {
            try {
                $object->{$name} = $value;
            } catch (\TypeError|UnexpectedValueException $e) {
                throw new UnexpectedValueException($e->getMessage(), previous: $e);
            }
        }

        return $object;
    }
}
