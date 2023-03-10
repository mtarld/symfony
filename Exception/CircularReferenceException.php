<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Exception;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class CircularReferenceException extends UnexpectedValueException
{
    /**
     * @param class-string $className
     */
    public function __construct(string $className)
    {
        parent::__construct(sprintf('A circular reference has been detected on class "%s".', $className));
    }
}
