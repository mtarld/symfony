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
use Symfony\Component\SerDes\Context\ContextBuilder\Serialize\GroupsAttributeContextBuilder;
use Symfony\Component\SerDes\SerializableResolver\SerializableResolverInterface;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithGroups;

class GroupsAttributeContextBuilderTest extends TestCase
{
    public function testAddPropertyGroupsToContext()
    {
        $serializableResolver = $this->createStub(SerializableResolverInterface::class);
        $serializableResolver->method('resolve')->willReturn(new \ArrayIterator([DummyWithGroups::class]));

        $contextBuilder = new GroupsAttributeContextBuilder($serializableResolver);

        $expectedContext = [
            '_symfony' => [
                'serialize' => [
                    'property_groups' => [
                        DummyWithGroups::class => [
                            'one' => ['one' => true],
                            'oneAndTwo' => ['one' => true, 'two' => true],
                            'twoAndThree' => ['two' => true, 'three' => true],
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
        $serializableResolver->method('resolve')->willReturn(new \ArrayIterator([DummyWithGroups::class]));

        $this->assertSame(['template_exists' => true], (new GroupsAttributeContextBuilder($serializableResolver))->build(['template_exists' => true]));
    }
}
