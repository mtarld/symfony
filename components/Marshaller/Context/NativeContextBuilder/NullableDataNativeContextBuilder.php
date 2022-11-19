<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\NativeContextBuilder;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\NullableDataOption;
use Symfony\Component\Marshaller\NativeContext\NativeContextBuilderInterface;

final class NullableDataNativeContextBuilder implements NativeContextBuilderInterface
{
    public function build(string $type, string $format, Context $context, array $nativeContext): array
    {
        if ($context->get(NullableDataOption::class)) {
            $nativeContext['nullable_data'] = true;
        }

        return $nativeContext;
    }
}
