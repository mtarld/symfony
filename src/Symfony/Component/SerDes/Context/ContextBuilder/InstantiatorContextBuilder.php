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
use Symfony\Component\SerDes\Exception\InvalidArgumentException;
use Symfony\Component\SerDes\Instantiator\InstantiatorInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 6.3
 */
final class InstantiatorContextBuilder implements ContextBuilderInterface
{
    public function __construct(
        private readonly InstantiatorInterface $lazyObjectInstantiator,
    ) {
    }

    public function buildSerializeContext(array $context, bool $willGenerateTemplate): array
    {
        return $context;
    }

    public function buildDeserializeContext(array $context): array
    {
        if (\is_callable($instantiator = $context['instantiator'] ?? null)) {
            return $context;
        }

        $context['instantiator'] = match ($instantiator) {
            'lazy', null => ($this->lazyObjectInstantiator)(...),
            'eager' => null,
            default => throw new InvalidArgumentException('Context value "instantiator" must be "lazy", "eager", or a valid callable.'),
        };

        return $context;
    }
}
