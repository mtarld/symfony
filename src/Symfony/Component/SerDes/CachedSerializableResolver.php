<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\SerDes\Util\CachedTrait;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class CachedSerializableResolver implements SerializableResolverInterface
{
    use CachedTrait;

    public function __construct(
        private readonly SerializableResolverInterface $resolver,
        private readonly CacheItemPoolInterface|null $cacheItemPool = null,
    ) {
    }

    public function resolve(): iterable
    {
        yield from $this->getCached('ser_des.serializable', function (): array {
            $serializables = [];
            foreach ($this->resolver->resolve() as $class => $serializable) {
                $serializables[$class] = $serializable;
            }

            return $serializables;
        });
    }
}
