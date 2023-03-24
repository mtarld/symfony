<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Context\ContextBuilder\Serialize;

use Symfony\Component\SerDes\Context\ContextBuilder\SerializeContextBuilderInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class SerializeHookContextBuilder implements SerializeContextBuilderInterface
{
    /**
     * @param iterable<string, callable> $serializeHooks
     */
    public function __construct(
        private readonly iterable $serializeHooks,
    ) {
    }

    public function build(array $context): array
    {
        if (true === ($context['template_exists'] ?? false)) {
            return $context;
        }

        foreach ($this->serializeHooks as $hookName => $hook) {
            if (!isset($context['hooks']['serialize'][$hookName])) {
                $context['hooks']['serialize'][$hookName] = $hook;
            }
        }

        return $context;
    }
}
