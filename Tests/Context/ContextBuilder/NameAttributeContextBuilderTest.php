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
use Symfony\Component\Marshaller\Context\ContextBuilder\NameAttributeContextBuilder;
use Symfony\Component\Marshaller\MarshallableResolverInterface;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\AnotherDummyWithNameAttributes;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithNameAttributes;

final class NameAttributeContextBuilderTest extends TestCase
{
    public function testAddPropertyNameToContext(): void
    {
        $marshallableResolver = $this->createStub(MarshallableResolverInterface::class);
        $marshallableResolver->method('resolve')->willReturn($this->getMarshallable());

        $cachePool = $this->createStub(CacheItemPoolInterface::class);

        $contextBuilder = new NameAttributeContextBuilder($marshallableResolver, $cachePool);

        $expectedContext = [
            '_symfony' => [
                'marshal' => [
                    'property_name' => [
                        sprintf('%s::$id', DummyWithNameAttributes::class) => '@id',
                        sprintf('%s::$name', AnotherDummyWithNameAttributes::class) => 'call_me_with',
                    ],
                ],
                'unmarshal' => [
                    'property_name' => [
                        sprintf('%s[@id]', DummyWithNameAttributes::class) => 'id',
                        sprintf('%s[call_me_with]', AnotherDummyWithNameAttributes::class) => 'name',
                    ],
                ],
            ],
        ];

        $this->assertSame($expectedContext, $contextBuilder->buildMarshalContext([], true));
        $this->assertSame($expectedContext, $contextBuilder->buildUnmarshalContext([]));
    }

    public function testCachePropertyNames(): void
    {
        $marshallableResolver = $this->createStub(MarshallableResolverInterface::class);
        $marshallableResolver->method('resolve')->willReturn($this->getMarshallable());

        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cachePool->expects($this->once())->method('getItem');
        $cachePool->expects($this->once())->method('save');

        $contextBuilder = new NameAttributeContextBuilder($marshallableResolver, $cachePool);

        $contextBuilder->buildMarshalContext([], true);
        $contextBuilder->buildUnmarshalContext([]);
    }

    public function testSkipWhenWontGenerateTemplate(): void
    {
        $marshallableResolver = $this->createStub(MarshallableResolverInterface::class);
        $cachePool = $this->createStub(CacheItemPoolInterface::class);

        $this->assertSame([], (new NameAttributeContextBuilder($marshallableResolver, $cachePool))->buildMarshalContext([], false));
    }

    /**
     * @return \Generator<class-string, null>
     */
    private function getMarshallable(): \Generator
    {
        yield DummyWithNameAttributes::class => null;
        yield AnotherDummyWithNameAttributes::class => null;
    }
}
