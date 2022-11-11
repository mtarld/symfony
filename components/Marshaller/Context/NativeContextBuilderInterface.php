<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context;

interface NativeContextBuilderInterface
{
    /**
     * @param array<string, mixed>
     *
     * @return array<string, mixed>
     */
    public function build(string $format, array $nativeContext): array;
}
