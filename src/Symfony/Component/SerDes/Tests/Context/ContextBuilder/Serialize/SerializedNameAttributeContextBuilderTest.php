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
use Symfony\Component\SerDes\Context\ContextBuilder\Serialize\SerializedNameAttributeContextBuilder;
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
                'serialize' => [
                    'property_name' => [
                        DummyWithNameAttributes::class => ['id' => '@id'],
                        AnotherDummyWithNameAttributes::class => ['name' => 'call_me_with'],
                    ],
                ],
            ],
        ];

        $this->assertSame($expectedContext, $contextBuilder->build([]));
    }

    public function testSkipWhenWontGenerateTemplate()
    {
        $serializableResolver = $this->createStub(SerializableResolverInterface::class);
        $serializableResolver->method('resolve')->willReturn(new \ArrayIterator([DummyWithNameAttributes::class, AnotherDummyWithNameAttributes::class]));

        $this->assertSame(['template_exists' => true], (new SerializedNameAttributeContextBuilder($serializableResolver))->build(['template_exists' => true]));
    }
}
