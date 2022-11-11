<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\NativeContextBuilder;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\TypeOption;

final class TypeNativeContextBuilder implements GenerationNativeContextBuilderInterface, MarshalNativeContextBuilderInterface
{
    public function forGeneration(string $type, string $format, Context $context, array $nativeContext): array
    {
        return $this->addTypeToNativeContext($context, $nativeContext);
    }

    public function forMarshal(mixed $data, string $format, Context $context, array $nativeContext): array
    {
        return $this->addTypeToNativeContext($context, $nativeContext);
    }

    /**
     * @param array<string, mixed> $nativeContext
     *
     * @return array<string, mixed>
     */
    private function addTypeToNativeContext(Context $context, array $nativeContext): array
    {
        if (null !== ($typeOption = $context->get(TypeOption::class))) {
            $nativeContext['type'] = $typeOption->type;
        }

        return $nativeContext;
    }
}
