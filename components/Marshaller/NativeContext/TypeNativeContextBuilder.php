<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\TypeOption;

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
