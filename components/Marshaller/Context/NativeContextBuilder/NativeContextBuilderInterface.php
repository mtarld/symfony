<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\NativeContextBuilder;

use Symfony\Component\Marshaller\Context\Context;

interface NativeContextBuilderInterface
{
    /**
     * @param array<string, mixed> $nativeContext
     *
     * @return array<string, mixed>
     */
    public function build(string $format, Context $context, array $nativeContext): array;
}
