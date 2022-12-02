<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext\Generation;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\HookOption;
use Symfony\Component\Marshaller\NativeContext\GenerationNativeContextBuilderInterface;

final class HookNativeContextBuilder implements GenerationNativeContextBuilderInterface
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
