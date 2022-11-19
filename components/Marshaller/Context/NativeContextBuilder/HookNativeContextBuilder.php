<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\NativeContextBuilder;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\HooksOption;
use Symfony\Component\Marshaller\NativeContext\NativeContextBuilderInterface;

final class HookNativeContextBuilder implements NativeContextBuilderInterface
{
    public function build(string $type, string $format, Context $context, array $nativeContext): array
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
