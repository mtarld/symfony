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
use Symfony\Component\SerDes\Context\ContextBuilder\Serialize\SerializeNameAttributeContextBuilder;
use Symfony\Component\SerDes\SerializableResolverInterface;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\AnotherDummyWithNameAttributes;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithNameAttributes;

class SerializeNameAttributeContextBuilderTest extends TestCase
{
    public function testAddPropertyNameToContext()
    {
        $serializableResolver = $this->createStub(SerializableResolverInterface::class);
        $serializableResolver->method('resolve')->willReturn(new \ArrayIterator([
            DummyWithNameAttributes::class => null,
            AnotherDummyWithNameAttributes::class => null,
        ]));

        $contextBuilder = new SerializeNameAttributeContextBuilder($serializableResolver);

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

        $this->assertSame(['template_exists' => true], (new SerializeNameAttributeContextBuilder($serializableResolver))->build(['template_exists' => true]));
    }
}
