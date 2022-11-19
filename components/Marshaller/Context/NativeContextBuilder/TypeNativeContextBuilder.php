<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\NativeContextBuilder;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\TypeOption;
use Symfony\Component\Marshaller\NativeContext\NativeContextBuilderInterface;

final class TypeNativeContextBuilder implements NativeContextBuilderInterface
{
    public function build(string $type, string $format, Context $context, array $nativeContext): array
    {
        if (null !== ($typeOption = $context->get(TypeOption::class))) {
            $nativeContext['type'] = $typeOption->type;
        }

        return $nativeContext;
    }
}
