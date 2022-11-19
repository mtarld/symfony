<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext;

use Symfony\Component\Marshaller\Context\Context;

interface NativeContextBuilderInterface
{
    /**
     * @param array<string, mixed> $nativeContext
     *
     * @return array<string, mixed>
     */
    public function build(string $type, string $format, Context $context, array $nativeContext): array;
}
