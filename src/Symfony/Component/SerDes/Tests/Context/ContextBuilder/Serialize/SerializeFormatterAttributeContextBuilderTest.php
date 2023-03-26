<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Context\ContextBuilder\Serialize;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Context\ContextBuilder\Serialize\SerializeFormatterAttributeContextBuilder;
use Symfony\Component\SerDes\SerializableResolverInterface;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\AnotherDummyWithFormatterAttributes;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithFormatterAttributes;

class SerializeFormatterAttributeContextBuilderTest extends TestCase
{
    public function testAddPropertyFormattersToContext()
    {
        $serializableResolver = $this->createStub(SerializableResolverInterface::class);
        $serializableResolver->method('resolve')->willReturn(new \ArrayIterator([
            DummyWithFormatterAttributes::class => null,
            AnotherDummyWithFormatterAttributes::class => null,
        ]));

        $contextBuilder = new SerializeFormatterAttributeContextBuilder($serializableResolver);

        $expectedContext = [
            '_symfony' => [
                'serialize' => [
                    'property_formatter' => [
                        DummyWithFormatterAttributes::class => [
                            'id' => [DummyWithFormatterAttributes::class, 'doubleAndCastToString'],
                        ],
                        AnotherDummyWithFormatterAttributes::class => [
                            'name' => [AnotherDummyWithFormatterAttributes::class, 'uppercase'],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame($expectedContext, $contextBuilder->build([]));
    }

    public function testSkipWhenWontGenerateTemplate()
    {
        $serializableResolver = $this->createStub(SerializableResolverInterface::class);

        $this->assertSame(['template_exists' => true], (new SerializeFormatterAttributeContextBuilder($serializableResolver))->build(['template_exists' => true]));
    }
}
