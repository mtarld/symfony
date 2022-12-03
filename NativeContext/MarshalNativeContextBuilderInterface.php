<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext;

use Symfony\Component\Marshaller\Context\Context;

// TODO marshal and generate folders?
interface MarshalNativeContextBuilderInterface
{
    /**
     * @param array<string, mixed> $nativeContext
     *
     * @return array<string, mixed>
     */
    public function build(Context $context, array $nativeContext): array;
}
