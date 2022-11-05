<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context;

interface OptionInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toNativeContext(): array;
}
