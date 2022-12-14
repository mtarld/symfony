<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Stream;

final class InputStream extends Stream
{
    public function __construct()
    {
        parent::__construct('php://input', 'placeholder');
    }
}
