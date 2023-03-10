<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Context\ContextBuilder;

use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Marshaller\Context\ContextBuilder\CachedContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilderInterface;

final class CachedContextBuilderTest extends TestCase
{
    public function testCacheContextValue(): void
    {
        $contextBuilder = $this->createMock(ContextBuilderInterface::class);
        $contextBuilder
            ->expects($this->once())
            ->method('buildMarshalContext')
            ->willReturn(['_symfony' => ['context_key' => 'value']]);

        $contextBuilder
            ->expects($this->once())
            ->method('buildUnmarshalContext')
            ->willReturn(['_symfony' => ['context_key' => 'value']]);

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cachePool->expects($this->exactly(2))->method('getItem')->withConsecutive(['cache_key_marshal'], ['cache_key_unmarshal']);
        $cachePool->expects($this->exactly(2))->method('save');

        $contextBuilder = new CachedContextBuilder($contextBuilder, 'context_key', 'cache_key', $cachePool);

        $this->assertSame(['not_cached' => 1, '_symfony' => ['context_key' => 'value']], $contextBuilder->buildMarshalContext(['not_cached' => 1], true));
        $this->assertSame(['not_cached' => 2, '_symfony' => ['context_key' => 'value']], $contextBuilder->buildMarshalContext(['not_cached' => 2], true));

        $this->assertSame(['not_cached' => 1, '_symfony' => ['context_key' => 'value']], $contextBuilder->buildUnmarshalContext(['not_cached' => 1]));
        $this->assertSame(['not_cached' => 2, '_symfony' => ['context_key' => 'value']], $contextBuilder->buildUnmarshalContext(['not_cached' => 2]));
    }
}
