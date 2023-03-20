<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Stream;

use Symfony\Component\Marshaller\Exception\RuntimeException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 6.4
 */
class Stream implements StreamInterface
{
    /**
     * @var resource
     */
    protected $resource;

    public function __construct(
        protected readonly string $filename,
        protected readonly string $mode,
    ) {
    }

    final public function resource()
    {
        if (null !== $this->resource) {
            return $this->resource;
        }

        if (false === $resource = @fopen($this->filename, $this->mode)) {
            throw new RuntimeException(sprintf('Cannot open "%s" resource', $this->filename));
        }

        return $this->resource = $resource;
    }

    final public function __toString(): string
    {
        rewind($this->resource());

        if (false === $content = stream_get_contents($this->resource())) {
            throw new RuntimeException(sprintf('Cannot read "%s" resource', $this->filename));
        }

        return $content;
    }
}
