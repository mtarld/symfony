<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Context\ContextBuilder;
use Symfony\Component\Serializer\Deserialize;
use Symfony\Component\Serializer\Deserialize\Context\DeserializeContext;
use Symfony\Component\Serializer\Deserialize\Hook\ObjectHook;
use Symfony\Component\Serializer\Deserialize\Instantiator\LazyInstantiator;
use Symfony\Component\Serializer\DeserializeInterface;
use Symfony\Component\Serializer\SerializableResolver\PathSerializableResolver;
use Symfony\Component\Serializer\Serialize\Hook\ObjectHookInterface as SerializeObjectHookInterface;
use Symfony\Component\Serializer\Stream\MemoryStream;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithFormatterAttributes;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithGenerics;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithGroups;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithNameAttributes;
use Symfony\Component\Serializer\Type\PhpstanTypeExtractor;
use Symfony\Component\Serializer\Type\ReflectionTypeExtractor;

class DeserializeTest extends TestCase
{
    private DeserializeInterface $deserialize;

    protected function setUp(): void
    {
        parent::setUp();

        $lazyObjectCacheDir = sprintf('%s/symfony_serializer_lazy_object', sys_get_temp_dir());

        if (is_dir($lazyObjectCacheDir)) {
            array_map('unlink', glob($lazyObjectCacheDir.'/*'));
            rmdir($lazyObjectCacheDir);
        }

        $this->deserialize = new Deserialize(
            new ContextBuilder(
                new PathSerializableResolver([__DIR__.'/Fixtures/Dto']),
                new LazyInstantiator($lazyObjectCacheDir),
                $this->createStub(SerializeObjectHookInterface::class),
                new ObjectHook(new PhpstanTypeExtractor(new ReflectionTypeExtractor())),
                $this->createStub(ContainerInterface::class),
                $this->createStub(ContainerInterface::class),
            ),
        );
    }

    public function testDeserialize()
    {
        $input = new MemoryStream();

        fwrite($input->resource(), '"foo"');
        rewind($input->resource());

        $result = ($this->deserialize)($input, 'string', 'json');

        $this->assertEquals('foo', $result);
    }

    public function testDeserializeHandleRawResource()
    {
        $input = fopen('php://memory', 'w+');

        fwrite($input, '"foo"');
        rewind($input);

        $result = ($this->deserialize)($input, 'string', 'json');

        $this->assertEquals('foo', $result);
    }

    public function testDeserializeCastContext()
    {
        $input = new MemoryStream();

        fwrite($input->resource(), '123456789012345678901234567890');
        rewind($input->resource());

        $result = ($this->deserialize)($input, 'string', 'json', (new DeserializeContext())->withJsonDecodeFlags(\JSON_BIGINT_AS_STRING));

        $this->assertEquals('123456789012345678901234567890', $result);
    }

    public function testDeserializeReadNameAttribute()
    {
        $input = new MemoryStream();

        fwrite($input->resource(), '{"@id":1,"name":"dummy"}');
        rewind($input->resource());

        $expectedResult = new DummyWithNameAttributes();
        $result = ($this->deserialize)($input, DummyWithNameAttributes::class, 'json', (new DeserializeContext())->withEagerInstantiation());

        $this->assertEquals($expectedResult, $result);
    }

    public function testDeserializeReadFormatterAttribute()
    {
        $input = new MemoryStream();

        fwrite($input->resource(), '{"id":"2","name":"dummy"}');
        rewind($input->resource());

        $expectedResult = new DummyWithFormatterAttributes();
        $result = ($this->deserialize)($input, DummyWithFormatterAttributes::class, 'json', (new DeserializeContext())->withEagerInstantiation());

        $this->assertEquals($expectedResult, $result);
    }

    public function testDeserializeReadGroupsAttribute()
    {
        $input = new MemoryStream();

        fwrite($input->resource(), '{"none":"updated","one":"updated","oneAndTwo":"updated","twoAndThree":"updated"}');
        rewind($input->resource());

        $expectedResult = new DummyWithGroups();
        $expectedResult->one = 'updated';
        $expectedResult->oneAndTwo = 'updated';

        $result = ($this->deserialize)($input, DummyWithGroups::class, 'json', (new DeserializeContext())->withGroups('one')->withLazyReading());

        $this->assertEquals($expectedResult, $result);
    }

    public function testDeserializeReadGenerics()
    {
        $input = new MemoryStream();

        fwrite($input->resource(), '{"dummies":[{"@id":1,"name":"dummy"},{"@id":1,"name":"dummy"}]}');
        rewind($input->resource());

        $expectedResult = new DummyWithGenerics();
        $expectedResult->dummies = [new DummyWithNameAttributes(), new DummyWithNameAttributes()];

        $result = ($this->deserialize)($input, sprintf('%s<%s>', DummyWithGenerics::class, DummyWithNameAttributes::class), 'json', ['instantiator' => 'eager']);

        $this->assertEquals($expectedResult, $result);
    }

    public function testDeserializeInstantiateLazyObject()
    {
        $input = new MemoryStream();

        fwrite($input->resource(), '{"id":1,"name":"dummy"}');
        rewind($input->resource());

        $result = ($this->deserialize)($input, DummyWithNameAttributes::class, 'json', (new DeserializeContext())->withLazyInstantiation());

        $lazyClassName = sprintf('%sGhost', preg_replace('/\\\\/', '', DummyWithNameAttributes::class));

        $this->assertInstanceof($lazyClassName, $result);
        $this->assertSame(1, $result->id);
    }
}
