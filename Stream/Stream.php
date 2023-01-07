<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Stream;

use Symfony\Component\Marshaller\Exception\RuntimeException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
class Stream implements StreamInterface
{
    /**
     * @var resource
     */
    protected $stream;

    public function __construct(
        protected readonly string $filename,
        protected readonly string $mode,
    ) {
    }

    final public function stream()
    {
        if (null !== $this->stream) {
            return $this->stream;
        }

        if (false === $stream = @fopen($this->filename, $this->mode)) {
            throw new RuntimeException(sprintf('Cannot open "%s" stream', $this->filename));
        }

        return $this->stream = $stream;
    }

    final public function __toString(): string
    {
        rewind($this->stream());

        if (false === $content = stream_get_contents($this->stream())) {
            throw new RuntimeException(sprintf('Cannot read "%s" stream', $this->filename));
        }

        return $content;
    }
}
