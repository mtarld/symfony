<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Context\ContextBuilder;

use Symfony\Component\SerDes\Context\ContextBuilderInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 6.3
 */
final class HookContextBuilder implements ContextBuilderInterface
{
    /**
     * @param iterable<string, callable> $serializeHooks
     * @param iterable<string, callable> $deserializeHooks
     */
    public function __construct(
        private readonly iterable $serializeHooks,
        private readonly iterable $deserializeHooks,
    ) {
    }

    public function buildSerializeContext(array $context, bool $willGenerateTemplate): array
    {
        if (!$willGenerateTemplate) {
            return $context;
        }

        foreach ($this->serializeHooks as $hookName => $hook) {
            if (!isset($context['hooks']['serialize'][$hookName])) {
                $context['hooks']['serialize'][$hookName] = $hook;
            }
        }

        return $context;
    }

    public function buildDeserializeContext(array $context): array
    {
        foreach ($this->deserializeHooks as $hookName => $hook) {
            if (!isset($context['hooks']['deserialize'][$hookName])) {
                $context['hooks']['deserialize'][$hookName] = $hook;
            }
        }

        return $context;
    }
}
