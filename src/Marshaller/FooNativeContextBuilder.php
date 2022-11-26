<?php

declare(strict_types=1);

namespace App\Marshaller;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\NativeContext\MarshalGenerateNativeContextBuilderInterface;

final class FooNativeContextBuilder implements MarshalGenerateNativeContextBuilderInterface
{
    public function build(string $type, Context $context, array $nativeContext): array
    {
        return $nativeContext + [
            'foo' => 'bar',
        ];
    }
}
