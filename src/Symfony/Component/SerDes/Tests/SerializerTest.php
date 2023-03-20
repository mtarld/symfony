<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Context\ContextBuilder\CachedContextBuilder;
use Symfony\Component\SerDes\Context\ContextBuilder\FormatterAttributeContextBuilder;
use Symfony\Component\SerDes\Context\ContextBuilder\HookContextBuilder;
use Symfony\Component\SerDes\Context\ContextBuilder\InstantiatorContextBuilder;
use Symfony\Component\SerDes\Context\ContextBuilder\NameAttributeContextBuilder;
use Symfony\Component\SerDes\Context\ContextBuilderInterface;
use Symfony\Component\SerDes\Context\DeserializeContext;
use Symfony\Component\SerDes\Context\SerializeContext;
use Symfony\Component\SerDes\Hook\Deserialize as DeserializeHook;
use Symfony\Component\SerDes\Hook\Serialize as SerializeHook;
use Symfony\Component\SerDes\Instantiator\LazyInstantiator;
use Symfony\Component\SerDes\SerializableResolver;
use Symfony\Component\SerDes\Serializer;
use Symfony\Component\SerDes\SerializerInterface;
use Symfony\Component\SerDes\Stream\MemoryStream;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithFormatterAttributes;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithGenerics;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithNameAttributes;
use Symfony\Component\SerDes\Type\PhpstanTypeExtractor;
use Symfony\Component\SerDes\Type\ReflectionTypeExtractor;

class SerializerTest extends TestCase
{
    private string $templateCacheDir;
    private string $lazyObjectCacheDir;

    private SerializerInterface $serializer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templateCacheDir = sprintf('%s/symfony_ser_des_template', sys_get_temp_dir());

        if (is_dir($this->templateCacheDir)) {
            array_map('unlink', glob($this->templateCacheDir.'/*'));
            rmdir($this->templateCacheDir);
        }

        $this->lazyObjectCacheDir = sprintf('%s/symfony_ser_des_lazy_object', sys_get_temp_dir());

        if (is_dir($this->lazyObjectCacheDir)) {
            array_map('unlink', glob($this->lazyObjectCacheDir.'/*'));
            rmdir($this->lazyObjectCacheDir);
        }

        $this->serializer = $this->createSerializer();
    }

    public function testSerialize()
    {
        $this->serializer->serialize(1, 'json', $output = new MemoryStream(), []);

        $this->assertSame('1', (string) $output);
    }

    public function testSerializeOverrideType()
    {
        $this->serializer->serialize(['foo' => 'bar'], 'json', $output = new MemoryStream(), ['type' => 'array<int, string>']);

        $this->assertSame('["bar"]', (string) $output);
    }

    public function testSerializeOverrideCacheDir()
    {
        $cacheDir = sprintf('%s/%s', sys_get_temp_dir(), uniqid('symfony_ser_des_tmp_'));

        $this->serializer->serialize('foo', 'json', new MemoryStream(), ['cache_dir' => $cacheDir]);

        $this->assertCount(1, glob($cacheDir.'/*'));

        array_map('unlink', glob($cacheDir.'/*'));
        rmdir($cacheDir);
    }

    public function testSerializeHandleRawResource()
    {
        $output = fopen('php://memory', 'w+');

        $this->serializer->serialize('123', 'json', $output, (new SerializeContext())->withJsonEncodeFlags(\JSON_NUMERIC_CHECK));

        rewind($output);

        $this->assertSame('123', stream_get_contents($output));
    }

    public function testSerializeCastContext()
    {
        $this->serializer->serialize('123', 'json', $output = new MemoryStream(), (new SerializeContext())->withJsonEncodeFlags(\JSON_NUMERIC_CHECK));

        $this->assertSame('123', (string) $output);
    }

    public function testSerializeCheckThatTemplateNotExist()
    {
        $contextBuilder = $this->createMock(ContextBuilderInterface::class);
        $contextBuilder
            ->expects($this->exactly(2))
            ->method('buildSerializeContext')
            ->withConsecutive(
                [['type' => 'int', 'cache_dir' => $this->templateCacheDir], true],
                [['type' => 'int', 'cache_dir' => $this->templateCacheDir], false],
            )
            ->willReturn(['type' => 'int', 'cache_dir' => $this->templateCacheDir]);

        $serializer = new Serializer([$contextBuilder], $this->templateCacheDir);

        $serializer->serialize(1, 'json', new MemoryStream(), []);
        $serializer->serialize(1, 'json', new MemoryStream(), []);
    }

    public function testSerializeReadNameAttribute()
    {
        $this->serializer->serialize(new DummyWithNameAttributes(), 'json', $output = new MemoryStream());

        $this->assertSame('{"@id":1,"name":"dummy"}', (string) $output);
    }

    public function testSerializeReadFormatterAttribute()
    {
        $this->serializer->serialize(new DummyWithFormatterAttributes(), 'json', $output = new MemoryStream());

        $this->assertSame('{"id":"2","name":"dummy"}', (string) $output);
    }

    public function testSerializeReadGenerics()
    {
        $dummy = new DummyWithGenerics();
        $dummy->dummies = [new DummyWithNameAttributes(), new DummyWithNameAttributes()];

        $this->serializer->serialize($dummy, 'json', $output = new MemoryStream(), [
            'type' => sprintf('%s<%s>', DummyWithGenerics::class, DummyWithNameAttributes::class),
        ]);

        $this->assertSame('{"dummies":[{"@id":1,"name":"dummy"},{"@id":1,"name":"dummy"}]}', (string) $output);
    }

    public function testDeserialize()
    {
        $input = new MemoryStream();

        fwrite($input->resource(), '"foo"');
        rewind($input->resource());

        $result = $this->serializer->deserialize($input, 'string', 'json');

        $this->assertEquals('foo', $result);
    }

    public function testDeserializeHandleRawResource()
    {
        $input = fopen('php://memory', 'w+');

        fwrite($input, '"foo"');
        rewind($input);

        $result = $this->serializer->deserialize($input, 'string', 'json');

        $this->assertEquals('foo', $result);
    }

    public function testDeserializeCastContext()
    {
        $input = new MemoryStream();

        fwrite($input->resource(), '123456789012345678901234567890');
        rewind($input->resource());

        $result = $this->serializer->deserialize($input, 'string', 'json', (new DeserializeContext())->withJsonDecodeFlags(\JSON_BIGINT_AS_STRING));

        $this->assertEquals('123456789012345678901234567890', $result);
    }

    public function testDeserializeReadNameAttribute()
    {
        $input = new MemoryStream();

        fwrite($input->resource(), '{"@id":1,"name":"dummy"}');
        rewind($input->resource());

        $expectedResult = new DummyWithNameAttributes();
        $result = $this->serializer->deserialize($input, DummyWithNameAttributes::class, 'json', ['instantiator' => 'eager']);

        $this->assertEquals($expectedResult, $result);
    }

    public function testDeserializeReadFormatterAttribute()
    {
        $input = new MemoryStream();

        fwrite($input->resource(), '{"id":"2","name":"dummy"}');
        rewind($input->resource());

        $expectedResult = new DummyWithFormatterAttributes();
        $result = $this->serializer->deserialize($input, DummyWithFormatterAttributes::class, 'json', ['instantiator' => 'eager']);

        $this->assertEquals($expectedResult, $result);
    }

    public function testDeserializeReadGenerics()
    {
        $input = new MemoryStream();

        fwrite($input->resource(), '{"dummies":[{"@id":1,"name":"dummy"},{"@id":1,"name":"dummy"}]}');
        rewind($input->resource());

        $expectedResult = new DummyWithGenerics();
        $expectedResult->dummies = [new DummyWithNameAttributes(), new DummyWithNameAttributes()];

        $result = $this->serializer->deserialize($input, sprintf('%s<%s>', DummyWithGenerics::class, DummyWithNameAttributes::class), 'json', ['instantiator' => 'eager']);

        $this->assertEquals($expectedResult, $result);
    }

    public function testDeserializeInstantiateLazyObject()
    {
        $input = new MemoryStream();

        fwrite($input->resource(), '{"id":1,"name":"dummy"}');
        rewind($input->resource());

        $result = $this->serializer->deserialize($input, DummyWithNameAttributes::class, 'json', ['instantiator' => 'lazy']);

        $lazyClassName = sprintf('%sGhost', preg_replace('/\\\\/', '', DummyWithNameAttributes::class));

        $this->assertInstanceof($lazyClassName, $result);
        $this->assertSame(1, $result->id);
    }

    private function createSerializer(): SerializerInterface
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());
        $serializableResolver = new SerializableResolver([__DIR__.'/Fixtures']);

        $contextBuilders = [
            new CachedContextBuilder(new FormatterAttributeContextBuilder($serializableResolver), 'property_formatter', 'formatter'),
            new CachedContextBuilder(new NameAttributeContextBuilder($serializableResolver), 'property_name', 'name'),
            new HookContextBuilder([
                'object' => (new SerializeHook\ObjectHook($typeExtractor))(...),
                'property' => (new SerializeHook\PropertyHook($typeExtractor))(...),
            ], [
                'object' => (new DeserializeHook\ObjectHook($typeExtractor))(...),
                'property' => (new DeserializeHook\PropertyHook($typeExtractor))(...),
            ]),
            new InstantiatorContextBuilder(new LazyInstantiator($this->lazyObjectCacheDir)),
        ];

        return new Serializer($contextBuilders, $this->templateCacheDir);
    }
}
