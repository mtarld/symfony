<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Context\ContextBuilder;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\ContextBuilder\FormatterAttributeContextBuilder;
use Symfony\Component\Marshaller\MarshallableResolverInterface;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\AnotherDummyWithFormatterAttributes;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithFormatterAttributes;

final class FormatterAttributeContextBuilderTest extends TestCase
{
    public function testAddPropertyFormattersToMarshalContext(): void
    {
        $marshallableResolver = $this->createStub(MarshallableResolverInterface::class);
        $marshallableResolver->method('resolve')->willReturn(new \ArrayIterator([
            DummyWithFormatterAttributes::class => null,
            AnotherDummyWithFormatterAttributes::class => null,
        ]));

        $contextBuilder = new FormatterAttributeContextBuilder($marshallableResolver);

        $expectedContext = [
            '_symfony' => [
                'property_formatter' => [
                    sprintf('%s::$id', DummyWithFormatterAttributes::class) => [DummyWithFormatterAttributes::class, 'doubleAndCastToString'],
                    sprintf('%s::$name', AnotherDummyWithFormatterAttributes::class) => [AnotherDummyWithFormatterAttributes::class, 'uppercase'],
                ],
            ],
        ];

        $this->assertSame($expectedContext, $contextBuilder->buildMarshalContext([], true));
    }

    public function testAddPropertyFormattersToUnmarshalContext(): void
    {
        $marshallableResolver = $this->createStub(MarshallableResolverInterface::class);
        $marshallableResolver->method('resolve')->willReturn(new \ArrayIterator([
            DummyWithFormatterAttributes::class => null,
            AnotherDummyWithFormatterAttributes::class => null,
        ]));

        $contextBuilder = new FormatterAttributeContextBuilder($marshallableResolver);

        $expectedContext = [
            '_symfony' => [
                'property_formatter' => [
                    sprintf('%s::$id', DummyWithFormatterAttributes::class) => [DummyWithFormatterAttributes::class, 'divideAndCastToInt'],
                    sprintf('%s::$name', AnotherDummyWithFormatterAttributes::class) => [AnotherDummyWithFormatterAttributes::class, 'lowercase'],
                ],
            ],
        ];

        $this->assertSame($expectedContext, $contextBuilder->buildUnmarshalContext([]));
    }

    public function testSkipWhenWontGenerateTemplate(): void
    {
        $marshallableResolver = $this->createStub(MarshallableResolverInterface::class);

        $this->assertSame([], (new FormatterAttributeContextBuilder($marshallableResolver))->buildMarshalContext([], false));
    }
}
