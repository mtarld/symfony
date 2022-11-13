<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\NativeContextBuilder;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\ValidateDataOption;

final class ValidateDataNativeContextBuilder implements GenerationNativeContextBuilderInterface
{
    public function forGeneration(string $type, string $format, Context $context, array $nativeContext): array
    {
        if ($context->get(ValidateDataOption::class)) {
            $nativeContext['validate_data'] = true;
        }

        return $nativeContext;
    }
}
