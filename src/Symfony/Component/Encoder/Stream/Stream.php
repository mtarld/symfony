<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\Stream;

use Symfony\Component\Encoder\Exception\InvalidResourceException;
use Symfony\Component\Encoder\Exception\RuntimeException;

/**
 * Opens and holds a PHP resource.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.1
 */
class Stream implements StreamInterface
{
    /**
     * @var resource
     */
    protected mixed $resource;

    public function __construct(string $filename, string $mode)
    {
        if (false === $this->resource = @fopen($filename, $mode)) {
            throw new RuntimeException(sprintf('Cannot open "%s" resource', $filename));
        }
    }

    final public function getResource(): mixed
    {
        return $this->resource;
    }

    final public function __toString(): string
    {
        if (false === @rewind($this->resource)) {
            throw new InvalidResourceException($this->resource);
        }

        if (false === $content = @stream_get_contents($this->resource)) {
            throw new InvalidResourceException($this->resource);
        }

        return $content;
    }
}
