<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\ContextBuilder\CachedContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilder\FormatterAttributeContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilder\HookContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilder\InstantiatorContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilder\NameAttributeContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilderInterface;
use Symfony\Component\Marshaller\Context\MarshalContext;
use Symfony\Component\Marshaller\Context\UnmarshalContext;
use Symfony\Component\Marshaller\Hook\Marshal as MarshalHook;
use Symfony\Component\Marshaller\Hook\Unmarshal as UnmarshalHook;
use Symfony\Component\Marshaller\Instantiator\LazyInstantiator;
use Symfony\Component\Marshaller\MarshallableResolver;
use Symfony\Component\Marshaller\Marshaller;
use Symfony\Component\Marshaller\MarshallerInterface;
use Symfony\Component\Marshaller\Stream\MemoryStream;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithFormatterAttributes;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithGenerics;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithNameAttributes;
use Symfony\Component\Marshaller\Type\PhpstanTypeExtractor;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;

class MarshallerTest extends TestCase
{
    private string $templateCacheDir;
    private string $lazyObjectCacheDir;

    private MarshallerInterface $marshaller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templateCacheDir = sprintf('%s/symfony_marshaller_template', sys_get_temp_dir());

        if (is_dir($this->templateCacheDir)) {
            array_map('unlink', glob($this->templateCacheDir.'/*'));
            rmdir($this->templateCacheDir);
        }

        $this->lazyObjectCacheDir = sprintf('%s/symfony_marshaller_lazy_object', sys_get_temp_dir());

        if (is_dir($this->lazyObjectCacheDir)) {
            array_map('unlink', glob($this->lazyObjectCacheDir.'/*'));
            rmdir($this->lazyObjectCacheDir);
        }

        $this->marshaller = $this->createMarshaller();
    }

    public function testMarshal()
    {
        $this->marshaller->marshal(1, 'json', $output = new MemoryStream(), []);

        $this->assertSame('1', (string) $output);
    }

    public function testMarshalOverrideType()
    {
        $this->marshaller->marshal(['foo' => 'bar'], 'json', $output = new MemoryStream(), ['type' => 'array<int, string>']);

        $this->assertSame('["bar"]', (string) $output);
    }

    public function testMarshalOverrideCacheDir()
    {
        $cacheDir = sprintf('%s/%s', sys_get_temp_dir(), uniqid('symfony_marshaller_tmp_'));

        $this->marshaller->marshal('foo', 'json', new MemoryStream(), ['cache_dir' => $cacheDir]);

        $this->assertCount(1, glob($cacheDir.'/*'));

        array_map('unlink', glob($cacheDir.'/*'));
        rmdir($cacheDir);
    }

    public function testMarshalHandleRawResource()
    {
        $output = fopen('php://memory', 'w+');

        $this->marshaller->marshal('123', 'json', $output, (new MarshalContext())->withJsonEncodeFlags(\JSON_NUMERIC_CHECK));

        rewind($output);

        $this->assertSame('123', stream_get_contents($output));
    }

    public function testMarshalCastContext()
    {
        $this->marshaller->marshal('123', 'json', $output = new MemoryStream(), (new MarshalContext())->withJsonEncodeFlags(\JSON_NUMERIC_CHECK));

        $this->assertSame('123', (string) $output);
    }

    public function testMarshalCheckThatTemplateNotExist()
    {
        $contextBuilder = $this->createMock(ContextBuilderInterface::class);
        $contextBuilder
            ->expects($this->exactly(2))
            ->method('buildMarshalContext')
            ->withConsecutive(
                [['type' => 'int', 'cache_dir' => $this->templateCacheDir], true],
                [['type' => 'int', 'cache_dir' => $this->templateCacheDir], false],
            )
            ->willReturn(['type' => 'int', 'cache_dir' => $this->templateCacheDir]);

        $marshaller = new Marshaller([$contextBuilder], $this->templateCacheDir);

        $marshaller->marshal(1, 'json', new MemoryStream(), []);
        $marshaller->marshal(1, 'json', new MemoryStream(), []);
    }

    public function testMarshalReadNameAttribute()
    {
        $this->marshaller->marshal(new DummyWithNameAttributes(), 'json', $output = new MemoryStream());

        $this->assertSame('{"@id":1,"name":"dummy"}', (string) $output);
    }

    public function testMarshalReadFormatterAttribute()
    {
        $this->marshaller->marshal(new DummyWithFormatterAttributes(), 'json', $output = new MemoryStream());

        $this->assertSame('{"id":"2","name":"dummy"}', (string) $output);
    }

    public function testMarshalReadGenerics()
    {
        $dummy = new DummyWithGenerics();
        $dummy->dummies = [new DummyWithNameAttributes(), new DummyWithNameAttributes()];

        $this->marshaller->marshal($dummy, 'json', $output = new MemoryStream(), [
            'type' => sprintf('%s<%s>', DummyWithGenerics::class, DummyWithNameAttributes::class),
        ]);

        $this->assertSame('{"dummies":[{"@id":1,"name":"dummy"},{"@id":1,"name":"dummy"}]}', (string) $output);
    }

    public function testUnmarshal()
    {
        $input = new MemoryStream();

        fwrite($input->resource(), '"foo"');
        rewind($input->resource());

        $result = $this->marshaller->unmarshal($input, 'string', 'json');

        $this->assertEquals('foo', $result);
    }

    public function testUnmarshalHandleRawResource()
    {
        $input = fopen('php://memory', 'w+');

        fwrite($input, '"foo"');
        rewind($input);

        $result = $this->marshaller->unmarshal($input, 'string', 'json');

        $this->assertEquals('foo', $result);
    }

    public function testUnmarshalCastContext()
    {
        $input = new MemoryStream();

        fwrite($input->resource(), '123456789012345678901234567890');
        rewind($input->resource());

        $result = $this->marshaller->unmarshal($input, 'string', 'json', (new UnmarshalContext())->withJsonDecodeFlags(\JSON_BIGINT_AS_STRING));

        $this->assertEquals('123456789012345678901234567890', $result);
    }

    public function testUnmarshalReadNameAttribute()
    {
        $input = new MemoryStream();

        fwrite($input->resource(), '{"@id":1,"name":"dummy"}');
        rewind($input->resource());

        $expectedResult = new DummyWithNameAttributes();
        $result = $this->marshaller->unmarshal($input, DummyWithNameAttributes::class, 'json', ['instantiator' => 'eager']);

        $this->assertEquals($expectedResult, $result);
    }

    public function testUnmarshalReadFormatterAttribute()
    {
        $input = new MemoryStream();

        fwrite($input->resource(), '{"id":"2","name":"dummy"}');
        rewind($input->resource());

        $expectedResult = new DummyWithFormatterAttributes();
        $result = $this->marshaller->unmarshal($input, DummyWithFormatterAttributes::class, 'json', ['instantiator' => 'eager']);

        $this->assertEquals($expectedResult, $result);
    }

    public function testUnmarshalReadGenerics()
    {
        $input = new MemoryStream();

        fwrite($input->resource(), '{"dummies":[{"@id":1,"name":"dummy"},{"@id":1,"name":"dummy"}]}');
        rewind($input->resource());

        $expectedResult = new DummyWithGenerics();
        $expectedResult->dummies = [new DummyWithNameAttributes(), new DummyWithNameAttributes()];

        $result = $this->marshaller->unmarshal($input, sprintf('%s<%s>', DummyWithGenerics::class, DummyWithNameAttributes::class), 'json', ['instantiator' => 'eager']);

        $this->assertEquals($expectedResult, $result);
    }

    public function testUnmarshalInstantiateLazyObject()
    {
        $input = new MemoryStream();

        fwrite($input->resource(), '{"id":1,"name":"dummy"}');
        rewind($input->resource());

        $result = $this->marshaller->unmarshal($input, DummyWithNameAttributes::class, 'json', ['instantiator' => 'lazy']);

        $lazyClassName = sprintf('%sGhost', preg_replace('/\\\\/', '', DummyWithNameAttributes::class));

        $this->assertInstanceof($lazyClassName, $result);
        $this->assertSame(1, $result->id);
    }

    private function createMarshaller(): MarshallerInterface
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());
        $marshallableResolver = new MarshallableResolver([__DIR__.'/Fixtures']);

        $contextBuilders = [
            new CachedContextBuilder(new FormatterAttributeContextBuilder($marshallableResolver), 'property_formatter', 'formatter'),
            new CachedContextBuilder(new NameAttributeContextBuilder($marshallableResolver), 'property_name', 'name'),
            new HookContextBuilder([
                'object' => (new MarshalHook\ObjectHook($typeExtractor))(...),
                'property' => (new MarshalHook\PropertyHook($typeExtractor))(...),
            ], [
                'object' => (new UnmarshalHook\ObjectHook($typeExtractor))(...),
                'property' => (new UnmarshalHook\PropertyHook($typeExtractor))(...),
            ]),
            new InstantiatorContextBuilder(new LazyInstantiator($this->lazyObjectCacheDir)),
        ];

        return new Marshaller($contextBuilders, $this->templateCacheDir);
    }
}
