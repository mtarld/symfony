<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Output;

interface StreamOutputInterface extends OutputInterface
{
    /**
     * @return resource
     */
    public function getStream();
}
