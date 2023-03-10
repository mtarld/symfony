<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Context\ContextBuilder;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\ContextBuilder\NameAttributeContextBuilder;
use Symfony\Component\Marshaller\MarshallableResolverInterface;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\AnotherDummyWithNameAttributes;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithNameAttributes;

final class NameAttributeContextBuilderTest extends TestCase
{
    public function testAddPropertyNameToMarshalContext(): void
    {
        $marshallableResolver = $this->createStub(MarshallableResolverInterface::class);
        $marshallableResolver->method('resolve')->willReturn($this->getMarshallable());

        $contextBuilder = new NameAttributeContextBuilder($marshallableResolver);

        $expectedContext = [
            '_symfony' => [
                'marshal' => [
                    'property_name' => [
                        sprintf('%s::$id', DummyWithNameAttributes::class) => '@id',
                        sprintf('%s::$name', AnotherDummyWithNameAttributes::class) => 'call_me_with',
                    ],
                ],
            ],
        ];

        $this->assertSame($expectedContext, $contextBuilder->buildMarshalContext([], true));
    }

    public function testAddPropertyNameToUnmarshalContext(): void
    {
        $marshallableResolver = $this->createStub(MarshallableResolverInterface::class);
        $marshallableResolver->method('resolve')->willReturn($this->getMarshallable());

        $contextBuilder = new NameAttributeContextBuilder($marshallableResolver);

        $expectedContext = [
            '_symfony' => [
                'unmarshal' => [
                    'property_name' => [
                        sprintf('%s[@id]', DummyWithNameAttributes::class) => 'id',
                        sprintf('%s[call_me_with]', AnotherDummyWithNameAttributes::class) => 'name',
                    ],
                ],
            ],
        ];

        $this->assertSame($expectedContext, $contextBuilder->buildUnmarshalContext([]));
    }

    public function testSkipWhenWontGenerateTemplate(): void
    {
        $marshallableResolver = $this->createStub(MarshallableResolverInterface::class);

        $this->assertSame([], (new NameAttributeContextBuilder($marshallableResolver))->buildMarshalContext([], false));
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
