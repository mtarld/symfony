<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Context\ContextBuilder;

use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\SerDes\Context\ContextBuilder\CachedContextBuilder;
use Symfony\Component\SerDes\Context\ContextBuilderInterface;

class CachedContextBuilderTest extends TestCase
{
    public function testCacheContextValue()
    {
        $contextBuilder = $this->createMock(ContextBuilderInterface::class);
        $contextBuilder
            ->expects($this->once())
            ->method('buildSerializeContext')
            ->willReturn(['_symfony' => ['context_key' => 'value']]);

        $contextBuilder
            ->expects($this->once())
            ->method('buildDeserializeContext')
            ->willReturn(['_symfony' => ['context_key' => 'value']]);

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cachePool->expects($this->exactly(2))->method('getItem')->withConsecutive(['cache_key_serialize'], ['cache_key_deserialize']);
        $cachePool->expects($this->exactly(2))->method('save');

        $contextBuilder = new CachedContextBuilder($contextBuilder, 'context_key', 'cache_key', $cachePool);

        $this->assertSame(['not_cached' => 1, '_symfony' => ['context_key' => 'value']], $contextBuilder->buildSerializeContext(['not_cached' => 1], true));
        $this->assertSame(['not_cached' => 2, '_symfony' => ['context_key' => 'value']], $contextBuilder->buildSerializeContext(['not_cached' => 2], true));

        $this->assertSame(['not_cached' => 1, '_symfony' => ['context_key' => 'value']], $contextBuilder->buildDeserializeContext(['not_cached' => 1]));
        $this->assertSame(['not_cached' => 2, '_symfony' => ['context_key' => 'value']], $contextBuilder->buildDeserializeContext(['not_cached' => 2]));
    }
}
