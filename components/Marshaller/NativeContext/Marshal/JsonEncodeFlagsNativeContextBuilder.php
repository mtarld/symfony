<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext\Marshal;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\JsonEncodeFlagsOption;
use Symfony\Component\Marshaller\NativeContext\MarshalNativeContextBuilderInterface;

final class JsonEncodeFlagsNativeContextBuilder implements MarshalNativeContextBuilderInterface
{
    public function build(Context $context, array $nativeContext): array
    {
        /** @var JsonEncodeFlagsOption|null $jsonEncodeFlagsOption */
        $jsonEncodeFlagsOption = $context->get(JsonEncodeFlagsOption::class);
        if (null === $jsonEncodeFlagsOption) {
            return $nativeContext;
        }

        $nativeContext['json_encode_flags'] = $jsonEncodeFlagsOption->flags;

        return $nativeContext;
    }
}
