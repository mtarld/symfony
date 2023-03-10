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
use Symfony\Component\Marshaller\Context\ContextBuilder\CachedFormatterAttributeContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilderInterface;

final class CachedFormatterAttributeContextBuilderTest extends TestCase
{
    public function testCacheMarshalPropertyFormatters(): void
    {
        $contextBuilder = $this->createMock(ContextBuilderInterface::class);
        $contextBuilder
            ->expects($this->once())
            ->method('buildMarshalContext')
            ->willReturn([
                '_symfony' => [
                    'marshal' => [
                        'property_formatter' => 'marshal_property_formatter',
                    ],
                ],
            ]);

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cachePool->expects($this->once())->method('getItem');
        $cachePool->expects($this->once())->method('save');

        $contextBuilder = new CachedFormatterAttributeContextBuilder($contextBuilder, $cachePool);

        $this->assertSame([
            'not_cached' => 1,
            '_symfony' => [
                'marshal' => [
                    'property_formatter' => 'marshal_property_formatter',
                ],
            ],
        ], $contextBuilder->buildMarshalContext(['not_cached' => 1], true));

        $this->assertSame([
            'not_cached' => 2,
            '_symfony' => [
                'marshal' => [
                    'property_formatter' => 'marshal_property_formatter',
                ],
            ],
        ], $contextBuilder->buildMarshalContext(['not_cached' => 2], true));

        $contextBuilder->buildUnmarshalContext([]);
    }

    public function testCacheUnmarshalPropertyFormatters(): void
    {
        $contextBuilder = $this->createMock(ContextBuilderInterface::class);
        $contextBuilder
            ->expects($this->once())
            ->method('buildUnmarshalContext')
            ->willReturn([
                '_symfony' => [
                    'unmarshal' => [
                        'property_formatter' => 'unmarshal_property_formatter',
                    ],
                ],
            ]);

        $contextBuilder->method('buildUnmarshalContext')->willReturn([]);

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cachePool->expects($this->once())->method('getItem');
        $cachePool->expects($this->once())->method('save');

        $contextBuilder = new CachedFormatterAttributeContextBuilder($contextBuilder, $cachePool);

        $this->assertSame([
            'not_cached' => 1,
            '_symfony' => [
                'unmarshal' => [
                    'property_formatter' => 'unmarshal_property_formatter',
                ],
            ],
        ], $contextBuilder->buildUnmarshalContext(['not_cached' => 1]));

        $this->assertSame([
            'not_cached' => 2,
            '_symfony' => [
                'unmarshal' => [
                    'property_formatter' => 'unmarshal_property_formatter',
                ],
            ],
        ], $contextBuilder->buildUnmarshalContext(['not_cached' => 2]));
    }
}
