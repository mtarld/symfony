<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Context\ContextBuilder\Unmarshal;

use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\ContextBuilder\Unmarshal\CachedFormatterAttributeContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilder\UnmarshalContextBuilderInterface;

final class CachedFormatterAttributeContextBuilderTest extends TestCase
{
    public function testAddPropertyFormatterToContext(): void
    {
        $context = new Context();

        $rawContext = [
            '_symfony' => [
                'unmarshal' => [
                    'property_formatter' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
        ];

        $contextBuilder = $this->createMock(UnmarshalContextBuilderInterface::class);
        $contextBuilder->expects($this->once())->method('build')->willReturn($rawContext);

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cachePool->expects($this->once())->method('getItem');
        $cachePool->expects($this->once())->method('save');

        $cachedContextBuilder = new CachedFormatterAttributeContextBuilder($contextBuilder, $cachePool);

        $this->assertSame($rawContext, $cachedContextBuilder->build('type', $context, []));
        $cachedContextBuilder->build('type', $context, []);
    }

    public function testNotAddNothingToContext(): void
    {
        $context = new Context();

        $contextBuilder = $this->createMock(UnmarshalContextBuilderInterface::class);
        $contextBuilder->expects($this->once())->method('build')->willReturn([
            '_symfony' => [
                'foo' => 'bar',
            ],
        ]);

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cachePool->expects($this->once())->method('getItem');
        $cachePool->expects($this->once())->method('save');

        $cachedContextBuilder = new CachedFormatterAttributeContextBuilder($contextBuilder, $cachePool);

        $this->assertSame([], $cachedContextBuilder->build('type', $context, []));
        $cachedContextBuilder->build('type', $context, []);
    }
}
