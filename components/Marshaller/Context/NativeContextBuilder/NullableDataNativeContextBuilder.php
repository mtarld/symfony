<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\NativeContextBuilder;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\NullableDataOption;

final class NullableDataNativeContextBuilder implements GenerationNativeContextBuilderInterface, MarshalNativeContextBuilderInterface
{
    public function forGeneration(string $type, string $format, Context $context, array $nativeContext): array
    {
        return $this->addNullableDataToNativeContext($context, $nativeContext);
    }

    public function forMarshal(mixed $data, string $format, Context $context, array $nativeContext): array
    {
        return $this->addNullableDataToNativeContext($context, $nativeContext);
    }

    /**
     * @param array<string, mixed> $nativeContext
     *
     * @return array<string, mixed>
     */
    private function addNullableDataToNativeContext(Context $context, array $nativeContext): array
    {
        if ($context->get(NullableDataOption::class)) {
            $nativeContext['nullable_data'] = true;
        }

        return $nativeContext;
    }
}
