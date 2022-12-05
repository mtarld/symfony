<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Generation;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\GenerationContextBuilderInterface;
use Symfony\Component\Marshaller\Context\Option\HookOption;

final class HookContextBuilder implements GenerationContextBuilderInterface
{
    public function build(string $type, Context $context, array $rawContext): array
    {
        /** @var HookOption|null $hookOption */
        $hookOption = $context->get(HookOption::class);
        if (null === $hookOption) {
            return $rawContext;
        }

        foreach ($hookOption->hooks as $hookName => $hook) {
            $rawContext['hooks'][$hookName] = $hook;
        }

        return $rawContext;
    }
}
