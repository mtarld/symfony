<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext\Marshal;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\TypeOption;
use Symfony\Component\Marshaller\NativeContext\MarshalNativeContextBuilderInterface;

final class TypeNativeContextBuilder implements MarshalNativeContextBuilderInterface
{
    public function build(Context $context, array $nativeContext): array
    {
        /** @var TypeOption|null $typeOption */
        $typeOption = $context->get(TypeOption::class);
        if (null === $typeOption) {
            return $nativeContext;
        }

        $nativeContext['type'] = $typeOption->type;

        return $nativeContext;
    }
}
