<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Attribute;

/**
 * Defines a callable that will be used to format the property data during decoding.
 *
 * The first argument of the callable is the input data.
 * It is possible to inject the configuration using the $config parameter.
 *
 * That callable must return the new data.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class DecodeFormatter
{
    /**
     * @var \Closure(mixed, array=): mixed
     */
    private \Closure $formatter;

    /**
     * @param callable(mixed, array=): mixed $formatter
     */
    public function __construct(callable $formatter)
    {
        $this->formatter = \Closure::fromCallable($formatter);
    }

    /**
     * @return \Closure(mixed, array=): mixed
     */
    public function getFormatter(): \Closure
    {
        return $this->formatter;
    }
}
