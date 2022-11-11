<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\NativeContextBuilder;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\HooksOption;

final class HookNativeContextBuilder implements GenerationNativeContextBuilderInterface
{
    public function forGeneration(string $type, string $format, Context $context, array $nativeContext): array
    {
        /** @var HooksOption|null $hooksOption */
        $hooksOption = $context->get(HooksOption::class);
        if (null === $hooksOption) {
            return $nativeContext;
        }

        foreach ($hooksOption->hooks as $hookName => $hook) {
            $nativeContext['hooks'][$hookName] = $hook;
        }

        return $nativeContext;
    }
}
