<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Context\ContextBuilder\Deserialize;

use Symfony\Component\SerDes\Context\ContextBuilder\DeserializeContextBuilderInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class DeserializeHookContextBuilder implements DeserializeContextBuilderInterface
{
    /**
     * @param iterable<string, callable> $deserializeHooks
     */
    public function __construct(
        private readonly iterable $deserializeHooks,
    ) {
    }

    public function build(array $context): array
    {
        foreach ($this->deserializeHooks as $hookName => $hook) {
            if (!isset($context['hooks']['deserialize'][$hookName])) {
                $context['hooks']['deserialize'][$hookName] = $hook;
            }
        }

        return $context;
    }
}
