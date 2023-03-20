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
use Symfony\Component\SerDes\Context\ContextBuilder\NameAttributeContextBuilder;
use Symfony\Component\SerDes\SerializableResolverInterface;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\AnotherDummyWithNameAttributes;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithNameAttributes;

class NameAttributeContextBuilderTest extends TestCase
{
    public function testAddPropertyNameToContext()
    {
        $serializableResolver = $this->createStub(SerializableResolverInterface::class);
        $serializableResolver->method('resolve')->willReturn(new \ArrayIterator([
            DummyWithNameAttributes::class => null,
            AnotherDummyWithNameAttributes::class => null,
        ]));

        $contextBuilder = new NameAttributeContextBuilder($serializableResolver);

        $expectedContext = [
            '_symfony' => [
                'property_name' => [
                    sprintf('%s::$id', DummyWithNameAttributes::class) => '@id',
                    sprintf('%s[@id]', DummyWithNameAttributes::class) => 'id',
                    sprintf('%s::$name', AnotherDummyWithNameAttributes::class) => 'call_me_with',
                    sprintf('%s[call_me_with]', AnotherDummyWithNameAttributes::class) => 'name',
                ],
            ],
        ];

        $this->assertSame($expectedContext, $contextBuilder->buildSerializeContext([], true));
        $this->assertSame($expectedContext, $contextBuilder->buildDeserializeContext([]));
    }

    public function testSkipWhenWontGenerateTemplate()
    {
        $serializableResolver = $this->createStub(SerializableResolverInterface::class);

        $this->assertSame([], (new NameAttributeContextBuilder($serializableResolver))->buildSerializeContext([], false));
    }
}
