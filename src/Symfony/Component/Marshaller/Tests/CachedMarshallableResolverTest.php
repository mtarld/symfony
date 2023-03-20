<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Marshaller\CachedMarshallableResolver;
use Symfony\Component\Marshaller\MarshallableResolverInterface;

class CachedMarshallableResolverTest extends TestCase
{
    public function testHitLocalCache()
    {
        $resolver = $this->createMock(MarshallableResolverInterface::class);
        $resolver->method('resolve')->willReturn(new \ArrayIterator(['Foo' => null, 'Bar' => null]));

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cachePool->expects($this->once())->method('getItem');
        $cachePool->expects($this->once())->method('save');

        $cachedResolver = new CachedMarshallableResolver($resolver, $cachePool);

        $this->assertSame(['Foo' => null, 'Bar' => null], iterator_to_array($cachedResolver->resolve()));
        iterator_to_array($cachedResolver->resolve());
    }

    public function testHitCachePool()
    {
        $resolver = $this->createMock(MarshallableResolverInterface::class);
        $resolver->expects($this->never())->method('resolve');

        $cacheItem = $this->createStub(CacheItemInterface::class);
        $cacheItem->method('isHit')->willReturn(true);
        $cacheItem->method('get')->willReturn(['Foo' => null]);

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cachePool->expects($this->once())->method('getItem')->willReturn($cacheItem);

        $cachedResolver = new CachedMarshallableResolver($resolver, $cachePool);

        $this->assertSame(['Foo' => null], iterator_to_array($cachedResolver->resolve()));
        iterator_to_array($cachedResolver->resolve());
    }

    public function testCacheException()
    {
        $resolver = $this->createMock(MarshallableResolverInterface::class);
        $resolver->method('resolve')->willReturn(new \ArrayIterator(['Foo' => null, 'Bar' => null]));

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cachePool->expects($this->once())->method('getItem')->willThrowException($this->createStub(CacheException::class));
        $cachePool->expects($this->never())->method('save');

        $cachedResolver = new CachedMarshallableResolver($resolver, $cachePool);

        $this->assertSame(['Foo' => null, 'Bar' => null], iterator_to_array($cachedResolver->resolve()));
        iterator_to_array($cachedResolver->resolve());
    }
}
