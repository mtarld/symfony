<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext;

use Symfony\Component\Marshaller\Context\Context;

interface MarshalNativeContextBuilderInterface
{
    /**
     * @param array<string, mixed> $nativeContext
     *
     * @return array<string, mixed>
     */
    public function buildMarshalNativeContext(string $type, Context $context, array $nativeContext): array;
}
