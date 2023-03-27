<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\SerializableResolver;

use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class CachedSerializableResolver implements SerializableResolverInterface
{
    public function __construct(
        private readonly SerializableResolverInterface $resolver,
        private readonly CacheItemPoolInterface|null $cacheItemPool = null,
    ) {
    }

    public function resolve(): iterable
    {
        if (null === $this->cacheItemPool) {
            yield from $this->resolver->resolve();

            return;
        }

        try {
            $item = $this->cacheItemPool->getItem('ser_des.serializable');
        } catch (CacheException) {
            yield from $this->resolver->resolve();

            return;
        }

        if (!$item->isHit()) {
            $serializables = [];
            foreach ($this->resolver->resolve() as $class => $serializable) {
                $serializables[$class] = $serializable;
            }

            $item->set($serializables);
        }

        yield from $item->get();
    }
}
