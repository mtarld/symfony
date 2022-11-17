<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\NativeContextBuilder;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\NullableDataOption;

final class NullableDataNativeContextBuilder implements NativeContextBuilderInterface
{
    public function build(string $format, Context $context, array $nativeContext): array
    {
        if ($context->get(NullableDataOption::class)) {
            $nativeContext['nullable_data'] = true;
        }

        return $nativeContext;
    }
}
