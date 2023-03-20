<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Marshaller\Util\CachedTrait;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class CachedMarshallableResolver implements MarshallableResolverInterface
{
    use CachedTrait;

    public function __construct(
        private readonly MarshallableResolverInterface $resolver,
        private readonly CacheItemPoolInterface|null $cacheItemPool = null,
    ) {
    }

    public function resolve(): iterable
    {
        yield from $this->getCached('marshaller.marshallable', function (): array {
            $marshallables = [];
            foreach ($this->resolver->resolve() as $class => $marshallable) {
                $marshallables[$class] = $marshallable;
            }

            return $marshallables;
        });
    }
}
