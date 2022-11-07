<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context;

interface OptionInterface
{
    /**
     * @param array<string, mixed> $nativeContext
     *
     * @return array<string, mixed>
     */
    public function mergeNativeContext(array $nativeContext): array;
}
