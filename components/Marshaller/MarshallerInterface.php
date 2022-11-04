<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Context\Context;

interface MarshallerInterface
{
    /**
     * @return iterable<string>
     */
    public function marshal(object $data, Context $context = null): iterable;
}
