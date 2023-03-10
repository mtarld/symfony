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
use Symfony\Component\Marshaller\Context\ContextBuilder\CachedNameAttributeContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilderInterface;

final class CachedNameAttributeContextBuilderTest extends TestCase
{
    public function testCacheMarshalPropertyNames(): void
    {
        $contextBuilder = $this->createMock(ContextBuilderInterface::class);
        $contextBuilder
            ->expects($this->once())
            ->method('buildMarshalContext')
            ->willReturn([
                '_symfony' => [
                    'marshal' => [
                        'property_name' => 'marshal_property_name',
                    ],
                ],
            ]);

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cachePool->expects($this->once())->method('getItem');
        $cachePool->expects($this->once())->method('save');

        $contextBuilder = new CachedNameAttributeContextBuilder($contextBuilder, $cachePool);

        $this->assertSame([
            'not_cached' => 1,
            '_symfony' => [
                'marshal' => [
                    'property_name' => 'marshal_property_name',
                ],
            ],
        ], $contextBuilder->buildMarshalContext(['not_cached' => 1], true));

        $this->assertSame([
            'not_cached' => 2,
            '_symfony' => [
                'marshal' => [
                    'property_name' => 'marshal_property_name',
                ],
            ],
        ], $contextBuilder->buildMarshalContext(['not_cached' => 2], true));

        $contextBuilder->buildUnmarshalContext([]);
    }

    public function testCacheUnmarshalPropertyNames(): void
    {
        $contextBuilder = $this->createMock(ContextBuilderInterface::class);
        $contextBuilder
            ->expects($this->once())
            ->method('buildUnmarshalContext')
            ->willReturn([
                '_symfony' => [
                    'unmarshal' => [
                        'property_name' => 'unmarshal_property_name',
                    ],
                ],
            ]);

        $contextBuilder->method('buildUnmarshalContext')->willReturn([]);

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cachePool->expects($this->once())->method('getItem');
        $cachePool->expects($this->once())->method('save');

        $contextBuilder = new CachedNameAttributeContextBuilder($contextBuilder, $cachePool);

        $this->assertSame([
            'not_cached' => 1,
            '_symfony' => [
                'unmarshal' => [
                    'property_name' => 'unmarshal_property_name',
                ],
            ],
        ], $contextBuilder->buildUnmarshalContext(['not_cached' => 1]));

        $this->assertSame([
            'not_cached' => 2,
            '_symfony' => [
                'unmarshal' => [
                    'property_name' => 'unmarshal_property_name',
                ],
            ],
        ], $contextBuilder->buildUnmarshalContext(['not_cached' => 2]));
    }
}
