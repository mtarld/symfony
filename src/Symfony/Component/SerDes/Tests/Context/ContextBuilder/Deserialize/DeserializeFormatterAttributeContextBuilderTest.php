<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Context\ContextBuilder\Deserialize;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Context\ContextBuilder\Deserialize\DeserializeFormatterAttributeContextBuilder;
use Symfony\Component\SerDes\SerializableResolverInterface;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\AnotherDummyWithFormatterAttributes;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithFormatterAttributes;

class DeserializeFormatterAttributeContextBuilderTest extends TestCase
{
    public function testAddPropertyFormattersToContext()
    {
        $serializableResolver = $this->createStub(SerializableResolverInterface::class);
        $serializableResolver->method('resolve')->willReturn(new \ArrayIterator([
            DummyWithFormatterAttributes::class => null,
            AnotherDummyWithFormatterAttributes::class => null,
        ]));

        $contextBuilder = new DeserializeFormatterAttributeContextBuilder($serializableResolver);

        $expectedContext = [
            '_symfony' => [
                'deserialize' => [
                    'property_formatter' => [
                        DummyWithFormatterAttributes::class => [
                            'id' => [DummyWithFormatterAttributes::class, 'divideAndCastToInt'],
                        ],
                        AnotherDummyWithFormatterAttributes::class => [
                            'name' => [AnotherDummyWithFormatterAttributes::class, 'lowercase'],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame($expectedContext, $contextBuilder->build([]));
    }
}
