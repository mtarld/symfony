<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Context;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Context\ContextBuilder;
use Symfony\Component\Serializer\Deserialize\Hook\ObjectHookInterface as DeserializeObjectHookInterface;
use Symfony\Component\Serializer\Deserialize\Instantiator\InstantiatorInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\SerializableResolver\SerializableResolverInterface;
use Symfony\Component\Serializer\Serialize\Hook\ObjectHookInterface as SerializeObjectHookInterface;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\AnotherDummyWithFormatterAttributes;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\AnotherDummyWithNameAttributes;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithFormatterAttributes;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithGroups;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithNameAttributes;

class ContextBuilderTest extends TestCase
{
    public function testSkipInstantiatorIfSerialization()
    {
        $context = [];

        $this->assertArrayNotHasKey('instantiator', $this->contextBuilder([])->build($context, isSerialization: true));
    }

    public function testAddEagerInstantiatorToContextByDefault()
    {
        $context = [];

        $this->assertNull($this->contextBuilder([])->build($context, isSerialization: false)['instantiator']);
    }

    public function testAddLazyInstantiatorToContext()
    {
        $context = ['instantiator' => 'lazy'];

        $this->assertInstanceOf(InstantiatorInterface::class, $this->contextBuilder([])->build($context, isSerialization: false)['instantiator']);
    }

    public function testAddEagerInstantiatorToContext()
    {
        $context = ['instantiator' => 'eager'];

        $this->assertNull($this->contextBuilder([])->build($context, isSerialization: false)['instantiator']);
    }

    public function testAddCustomInstantiatorToContext()
    {
        $context = ['instantiator' => $customInstantiator = static function () {}];

        $this->assertSame($customInstantiator, $this->contextBuilder([])->build($context, isSerialization: false)['instantiator']);
    }

    public function testThrowIfInvalidInstantiator()
    {
        $context = ['instantiator' => 'foo'];

        $this->expectException(InvalidArgumentException::class);

        $this->contextBuilder([])->build($context, isSerialization: false);
    }

    public function testAddPropertyGroups()
    {
        $this->assertSame([
            DummyWithGroups::class => [
                'one' => ['one' => true],
                'oneAndTwo' => ['one' => true, 'two' => true],
                'twoAndThree' => ['two' => true, 'three' => true],
            ],
        ], $this->contextBuilder([DummyWithGroups::class])->build([], isSerialization: true)['_symfony']['serialize']['property_groups']);

        $this->assertSame([
            DummyWithGroups::class => [
                'one' => ['one' => true],
                'oneAndTwo' => ['one' => true, 'two' => true],
                'twoAndThree' => ['two' => true, 'three' => true],
            ],
        ], $this->contextBuilder([DummyWithGroups::class])->build([], isSerialization: false)['_symfony']['deserialize']['property_groups']);
    }

    public function testAddPropertyName()
    {
        $this->assertSame([
            DummyWithNameAttributes::class => ['id' => '@id'],
            AnotherDummyWithNameAttributes::class => ['name' => 'call_me_with'],
        ], $this->contextBuilder([DummyWithNameAttributes::class, AnotherDummyWithNameAttributes::class])->build([], isSerialization: true)['_symfony']['serialize']['property_name']);

        $this->assertSame([
            DummyWithNameAttributes::class => ['@id' => 'id'],
            AnotherDummyWithNameAttributes::class => ['call_me_with' => 'name'],
        ], $this->contextBuilder([DummyWithNameAttributes::class, AnotherDummyWithNameAttributes::class])->build([], isSerialization: false)['_symfony']['deserialize']['property_name']);
    }

    public function testAddPropertyFormatter()
    {
        $this->assertSame([
            DummyWithFormatterAttributes::class => [
                'id' => [DummyWithFormatterAttributes::class, 'doubleAndCastToString'],
            ],
            AnotherDummyWithFormatterAttributes::class => [
                'name' => [AnotherDummyWithFormatterAttributes::class, 'uppercase'],
            ],
        ], $this->contextBuilder([DummyWithFormatterAttributes::class, AnotherDummyWithFormatterAttributes::class])->build([], isSerialization: true)['_symfony']['serialize']['property_formatter']);

        $this->assertSame([
            DummyWithFormatterAttributes::class => [
                'id' => [DummyWithFormatterAttributes::class, 'divideAndCastToInt'],
            ],
            AnotherDummyWithFormatterAttributes::class => [
                'name' => [AnotherDummyWithFormatterAttributes::class, 'lowercase'],
            ],
        ], $this->contextBuilder([DummyWithFormatterAttributes::class, AnotherDummyWithFormatterAttributes::class])->build([], isSerialization: false)['_symfony']['deserialize']['property_formatter']);
    }

    public function testAddObjectHook()
    {
        $objectHook = static function () {};

        $context = $this->contextBuilder([])->build(['hooks' => ['serialize' => ['object' => $objectHook]]], isSerialization: true);
        $this->assertSame($objectHook, $context['hooks']['serialize']['object']);

        $context = $this->contextBuilder([])->build([], isSerialization: true);
        $this->assertInstanceOf(SerializeObjectHookInterface::class, $context['hooks']['serialize']['object']);

        $context = $this->contextBuilder([])->build(['hooks' => ['deserialize' => ['object' => $objectHook]]], isSerialization: false);
        $this->assertSame($objectHook, $context['hooks']['deserialize']['object']);

        $context = $this->contextBuilder([])->build([], isSerialization: false);
        $this->assertInstanceOf(DeserializeObjectHookInterface::class, $context['hooks']['deserialize']['object']);
    }

    public function testAddServices()
    {
        $context = $this->contextBuilder([])->build([], isSerialization: true);
        $this->assertInstanceOf(ContainerInterface::class, $context['services']['serialize']);

        $context = $this->contextBuilder([])->build([], isSerialization: false);
        $this->assertInstanceOf(ContainerInterface::class, $context['services']['deserialize']);
    }

    /**
     * @param list<class-string> $serializable
     */
    private function contextBuilder(array $serializable): ContextBuilder
    {
        $serializableResolver = $this->createStub(SerializableResolverInterface::class);
        $serializableResolver->method('resolve')->willReturn(new \ArrayIterator($serializable));

        return new ContextBuilder(
            $serializableResolver,
            $this->createStub(InstantiatorInterface::class),
            $this->createStub(SerializeObjectHookInterface::class),
            $this->createStub(DeserializeObjectHookInterface::class),
            $this->createStub(ContainerInterface::class),
            $this->createStub(ContainerInterface::class),
        );
    }
}
