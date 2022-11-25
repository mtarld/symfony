<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\HookOption;

final class HookNativeContextBuilder implements MarshalGenerateNativeContextBuilderInterface
{
    public function build(string $type, Context $context, array $nativeContext): array
    {
        /** @var HookOption|null $hookOption */
        $hookOption = $context->get(HookOption::class);
        if (null === $hookOption) {
            return $nativeContext;
        }

        foreach ($hookOption->hooks as $hookName => $hook) {
            $nativeContext['hooks'][$hookName] = $hook;
        }

        return $nativeContext;
    }
}
