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
use Symfony\Component\Serializer\Deserialize\Hook\ObjectHookInterface as DeserializeObjectHookInterface;
use Symfony\Component\Serializer\Deserialize\Instantiator\InstantiatorInterface;
use Symfony\Component\Serializer\SerializableResolver\PathSerializableResolver;
use Symfony\Component\Serializer\Serialize;
use Symfony\Component\Serializer\Serialize\Context\SerializeContext;
use Symfony\Component\Serializer\Serialize\Hook\ObjectHook;
use Symfony\Component\Serializer\SerializeInterface;
use Symfony\Component\Serializer\Stream\MemoryStream;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithFormatterAttributes;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithGenerics;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithGroups;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithNameAttributes;
use Symfony\Component\Serializer\Type\PhpstanTypeExtractor;
use Symfony\Component\Serializer\Type\ReflectionTypeExtractor;

class SerializeTest extends TestCase
{
    private SerializeInterface $serialize;

    protected function setUp(): void
    {
        parent::setUp();

        $templateCacheDir = sprintf('%s/symfony_serializer_template', sys_get_temp_dir());

        if (is_dir($templateCacheDir)) {
            array_map('unlink', glob($templateCacheDir.'/*'));
            rmdir($templateCacheDir);
        }

        $this->serialize = new Serialize(
            new ContextBuilder(
                new PathSerializableResolver([__DIR__.'/Fixtures/Dto']),
                $this->createStub(InstantiatorInterface::class),
                new ObjectHook(new PhpstanTypeExtractor(new ReflectionTypeExtractor())),
                $this->createStub(DeserializeObjectHookInterface::class),
                $this->createStub(ContainerInterface::class),
                $this->createStub(ContainerInterface::class),
            ),
            $templateCacheDir,
        );
    }

    public function testSerialize()
    {
        ($this->serialize)(1, 'json', $output = new MemoryStream(), []);

        $this->assertSame('1', (string) $output);
    }

    public function testSerializeOverrideType()
    {
        ($this->serialize)(['foo' => 'bar'], 'json', $output = new MemoryStream(), ['type' => 'array<int, string>']);

        $this->assertSame('["bar"]', (string) $output);
    }

    public function testSerializeOverrideCacheDir()
    {
        $cacheDir = sprintf('%s/%s', sys_get_temp_dir(), uniqid('symfony_serializer_tmp_'));

        ($this->serialize)('foo', 'json', new MemoryStream(), ['cache_dir' => $cacheDir]);

        $this->assertCount(1, glob($cacheDir.'/*'));

        array_map('unlink', glob($cacheDir.'/*'));
        rmdir($cacheDir);
    }

    public function testSerializeHandleRawResource()
    {
        $output = fopen('php://memory', 'w+');

        ($this->serialize)('123', 'json', $output, (new SerializeContext())->withJsonEncodeFlags(\JSON_NUMERIC_CHECK));

        rewind($output);

        $this->assertSame('123', stream_get_contents($output));
    }

    public function testSerializeCastContext()
    {
        ($this->serialize)('123', 'json', $output = new MemoryStream(), (new SerializeContext())->withJsonEncodeFlags(\JSON_NUMERIC_CHECK));

        $this->assertSame('123', (string) $output);
    }

    public function testSerializeReadNameAttribute()
    {
        ($this->serialize)(new DummyWithNameAttributes(), 'json', $output = new MemoryStream());

        $this->assertSame('{"@id":1,"name":"dummy"}', (string) $output);
    }

    public function testSerializeReadFormatterAttribute()
    {
        ($this->serialize)(new DummyWithFormatterAttributes(), 'json', $output = new MemoryStream());

        $this->assertSame('{"id":"2","name":"dummy"}', (string) $output);
    }

    public function testSerializeReadGroupsAttribute()
    {
        ($this->serialize)(new DummyWithGroups(), 'json', $output = new MemoryStream(), (new SerializeContext())->withGroups('one'));

        $this->assertSame('{"one":"one","oneAndTwo":"oneAndTwo"}', (string) $output);
    }

    public function testSerializeReadGenerics()
    {
        $dummy = new DummyWithGenerics();
        $dummy->dummies = [new DummyWithNameAttributes(), new DummyWithNameAttributes()];

        ($this->serialize)($dummy, 'json', $output = new MemoryStream(), [
            'type' => sprintf('%s<%s>', DummyWithGenerics::class, DummyWithNameAttributes::class),
        ]);

        $this->assertSame('{"dummies":[{"@id":1,"name":"dummy"},{"@id":1,"name":"dummy"}]}', (string) $output);
    }
}
