<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Context\ContextBuilder\Generation;

use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\ContextBuilder\Generation\CachedNameAttributeContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilder\GenerationContextBuilderInterface;

final class CachedNameAttributeContextBuilderTest extends TestCase
{
    public function testAddPropertyNameToContext(): void
    {
        $context = new Context();

        $rawContext = [
            '_symfony' => [
                'marshal' => [
                    'property_name' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
        ];

        $contextBuilder = $this->createMock(GenerationContextBuilderInterface::class);
        $contextBuilder->expects($this->once())->method('build')->willReturn($rawContext);

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cachePool->expects($this->once())->method('getItem');
        $cachePool->expects($this->once())->method('save');

        $cachedContextBuilder = new CachedNameAttributeContextBuilder($contextBuilder, $cachePool);

        $this->assertSame($rawContext, $cachedContextBuilder->build('type', $context, []));
        $cachedContextBuilder->build('type', $context, []);
    }

    public function testNotAddNothingToContext(): void
    {
        $context = new Context();

        $contextBuilder = $this->createMock(GenerationContextBuilderInterface::class);
        $contextBuilder->expects($this->once())->method('build')->willReturn([
            '_symfony' => [
                'foo' => 'bar',
            ],
        ]);

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cachePool->expects($this->once())->method('getItem');
        $cachePool->expects($this->once())->method('save');

        $cachedContextBuilder = new CachedNameAttributeContextBuilder($contextBuilder, $cachePool);

        $this->assertSame([], $cachedContextBuilder->build('type', $context, []));
        $cachedContextBuilder->build('type', $context, []);
    }
}
