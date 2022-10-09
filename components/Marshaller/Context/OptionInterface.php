<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context;

interface OptionInterface
{
    public function signature(): string;
}
