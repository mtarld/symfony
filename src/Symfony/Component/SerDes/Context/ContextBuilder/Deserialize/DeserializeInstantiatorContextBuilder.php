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
use Symfony\Component\SerDes\Exception\InvalidArgumentException;
use Symfony\Component\SerDes\Instantiator\InstantiatorInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class DeserializeInstantiatorContextBuilder implements DeserializeContextBuilderInterface
{
    public function __construct(
        private readonly InstantiatorInterface $lazyObjectInstantiator,
    ) {
    }

    public function build(array $context): array
    {
        if (\is_callable($instantiator = $context['instantiator'] ?? null)) {
            return $context;
        }

        $context['instantiator'] = match ($instantiator) {
            'eager', null => null,
            'lazy' => ($this->lazyObjectInstantiator)(...),
            default => throw new InvalidArgumentException('Context value "instantiator" must be "lazy", "eager", or a valid callable.'),
        };

        return $context;
    }
}
