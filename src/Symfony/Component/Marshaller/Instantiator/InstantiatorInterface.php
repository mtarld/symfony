<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Instantiator;

use Symfony\Component\Marshaller\Exception\InvalidConstructorArgumentException;
use Symfony\Component\Marshaller\Exception\UnexpectedTypeException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 6.4
 */
interface InstantiatorInterface
{
    /**
     * @template T of object
     *
     * @param \ReflectionClass<T>              $class
     * @param array<string, callable(): mixed> $propertiesValues
     * @param array<string, mixed>             $context
     *
     * @return T
     *
     * @throws InvalidConstructorArgumentException
     * @throws UnexpectedTypeException
     */
    public function __invoke(\ReflectionClass $class, array $propertiesValues, array $context): object;
}
