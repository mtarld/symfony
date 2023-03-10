<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context\ContextBuilder;

use Symfony\Component\Marshaller\Context\ContextBuilderInterface;
use Symfony\Component\Marshaller\Exception\InvalidArgumentException;
use Symfony\Component\Marshaller\Instantiator\InstantiatorInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class InstantiatorContextBuilder implements ContextBuilderInterface
{
    public function __construct(
        private readonly InstantiatorInterface $lazyObjectInstantiator,
    ) {
    }

    public function buildMarshalContext(array $context, bool $willGenerateTemplate): array
    {
        return $context;
    }

    public function buildUnmarshalContext(array $context): array
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
