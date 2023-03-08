<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context\ContextBuilder;

use Symfony\Component\Marshaller\Context\ContextBuilderInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class HookContextBuilder implements ContextBuilderInterface
{
    /**
     * @param iterable<string, callable> $marshalHooks
     * @param iterable<string, callable> $unmarshalHooks
     */
    public function __construct(
        private readonly iterable $marshalHooks,
        private readonly iterable $unmarshalHooks,
    ) {
    }

    public function buildMarshalContext(array $context, bool $willGenerateTemplate): array
    {
        if (!$willGenerateTemplate) {
            return $context;
        }

        return $this->addHooks($this->marshalHooks, $context);
    }

    public function buildUnmarshalContext(array $context): array
    {
        return $this->addHooks($this->unmarshalHooks, $context);
    }

    /**
     * @param iterable<string, callable> $hooks
     * @param array<string, mixed>       $context
     *
     * @return array<string, mixed>
     */
    private function addHooks(iterable $hooks, array $context): array
    {
        foreach ($hooks as $hookName => $hook) {
            if (!isset($context['hooks'][$hookName])) {
                $context['hooks'][$hookName] = $hook;
            }
        }

        return $context;
    }
}
