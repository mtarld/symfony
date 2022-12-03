<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Output;

interface OutputInterface extends \Stringable
{
    /**
     * @return resource
     */
    public function stream();
}
