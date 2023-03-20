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
use Symfony\Component\SerDes\Context\ContextBuilder\FormatterAttributeContextBuilder;
use Symfony\Component\SerDes\SerializableResolverInterface;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\AnotherDummyWithFormatterAttributes;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithFormatterAttributes;

class FormatterAttributeContextBuilderTest extends TestCase
{
    public function testAddPropertyFormattersToContext()
    {
        $serializableResolver = $this->createStub(SerializableResolverInterface::class);
        $serializableResolver->method('resolve')->willReturn(new \ArrayIterator([
            DummyWithFormatterAttributes::class => null,
            AnotherDummyWithFormatterAttributes::class => null,
        ]));

        $contextBuilder = new FormatterAttributeContextBuilder($serializableResolver);

        $expectedContext = [
            '_symfony' => [
                'property_formatter' => [
                    sprintf('%s::$id', DummyWithFormatterAttributes::class) => [
                        'serialize' => [DummyWithFormatterAttributes::class, 'doubleAndCastToString'],
                        'deserialize' => [DummyWithFormatterAttributes::class, 'divideAndCastToInt'],
                    ],
                    sprintf('%s::$name', AnotherDummyWithFormatterAttributes::class) => [
                        'serialize' => [AnotherDummyWithFormatterAttributes::class, 'uppercase'],
                        'deserialize' => [AnotherDummyWithFormatterAttributes::class, 'lowercase'],
                    ],
                ],
            ],
        ];

        $this->assertSame($expectedContext, $contextBuilder->buildSerializeContext([], true));
        $this->assertSame($expectedContext, $contextBuilder->buildDeserializeContext([]));
    }

    public function testSkipWhenWontGenerateTemplate()
    {
        $serializableResolver = $this->createStub(SerializableResolverInterface::class);

        $this->assertSame([], (new FormatterAttributeContextBuilder($serializableResolver))->buildSerializeContext([], false));
    }
}
