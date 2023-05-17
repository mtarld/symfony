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
use Symfony\Component\SerDes\Context\ContextBuilder\Deserialize\SerializedNameAttributeContextBuilder;
use Symfony\Component\SerDes\SerializableResolver\SerializableResolverInterface;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\AnotherDummyWithNameAttributes;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithNameAttributes;

class SerializedNameAttributeContextBuilderTest extends TestCase
{
    public function testAddPropertyNameToContext()
    {
        $serializableResolver = $this->createStub(SerializableResolverInterface::class);
        $serializableResolver->method('resolve')->willReturn(new \ArrayIterator([DummyWithNameAttributes::class, AnotherDummyWithNameAttributes::class]));

        $contextBuilder = new SerializedNameAttributeContextBuilder($serializableResolver);

        $expectedContext = [
            '_symfony' => [
                'deserialize' => [
                    'property_name' => [
                        DummyWithNameAttributes::class => ['@id' => 'id'],
                        AnotherDummyWithNameAttributes::class => ['call_me_with' => 'name'],
                    ],
                ],
            ],
        ];

        $this->assertSame($expectedContext, $contextBuilder->build([]));
    }
}
