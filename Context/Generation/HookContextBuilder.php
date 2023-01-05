<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context\Generation;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\GenerationContextBuilderInterface;
use Symfony\Component\Marshaller\Context\Option\HookOption;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
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
