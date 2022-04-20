<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Output;

interface StreamOutputInterface
{
    /**
     * @return resource
     */
    public function getStream();
}
