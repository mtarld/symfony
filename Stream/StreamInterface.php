<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Stream;

interface StreamInterface extends \Stringable
{
    /**
     * @return resource
     */
    public function stream();
}
