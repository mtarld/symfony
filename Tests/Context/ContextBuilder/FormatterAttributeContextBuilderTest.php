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
use Symfony\Component\Marshaller\Context\ContextBuilder\FormatterAttributeContextBuilder;
use Symfony\Component\Marshaller\MarshallableResolverInterface;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\AnotherDummyWithFormatterAttributes;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithFormatterAttributes;

final class FormatterAttributeContextBuilderTest extends TestCase
{
    public function testAddPropertyFormattersToContext(): void
    {
        $marshallableResolver = $this->createStub(MarshallableResolverInterface::class);
        $marshallableResolver->method('resolve')->willReturn($this->getMarshallable());

        $cachePool = $this->createStub(CacheItemPoolInterface::class);

        $contextBuilder = new FormatterAttributeContextBuilder($marshallableResolver, $cachePool);

        $expectedContext = [
            '_symfony' => [
                'marshal' => [
                    'property_formatter' => [
                        sprintf('%s::$id', DummyWithFormatterAttributes::class) => [DummyWithFormatterAttributes::class, 'doubleAndCastToString'],
                        sprintf('%s::$name', AnotherDummyWithFormatterAttributes::class) => [AnotherDummyWithFormatterAttributes::class, 'uppercase'],
                    ],
                ],
                'unmarshal' => [
                    'property_formatter' => [
                        sprintf('%s::$id', DummyWithFormatterAttributes::class) => [DummyWithFormatterAttributes::class, 'divideAndCastToInt'],
                        sprintf('%s::$name', AnotherDummyWithFormatterAttributes::class) => [AnotherDummyWithFormatterAttributes::class, 'lowercase'],
                    ],
                ],
            ],
        ];

        $this->assertSame($expectedContext, $contextBuilder->buildMarshalContext([], true));
        $this->assertSame($expectedContext, $contextBuilder->buildUnmarshalContext([]));
    }

    public function testCachePropertyFormatters(): void
    {
        $marshallableResolver = $this->createStub(MarshallableResolverInterface::class);
        $marshallableResolver->method('resolve')->willReturn($this->getMarshallable());

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cachePool->expects($this->once())->method('getItem');
        $cachePool->expects($this->once())->method('save');

        $contextBuilder = new FormatterAttributeContextBuilder($marshallableResolver, $cachePool);

        $contextBuilder->buildMarshalContext([], true);
        $contextBuilder->buildUnmarshalContext([]);
    }

    public function testSkipWhenWontGenerateTemplate(): void
    {
        $marshallableResolver = $this->createStub(MarshallableResolverInterface::class);
        $cachePool = $this->createStub(CacheItemPoolInterface::class);

        $this->assertSame([], (new FormatterAttributeContextBuilder($marshallableResolver, $cachePool))->buildMarshalContext([], false));
    }

    /**
     * @return \Generator<class-string, null>
     */
    private function getMarshallable(): \Generator
    {
        yield DummyWithFormatterAttributes::class => null;
        yield AnotherDummyWithFormatterAttributes::class => null;
    }
}
