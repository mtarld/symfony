<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\JsonEncoder\Encode\EncodeAs;
use Symfony\Component\JsonEncoder\JsonEncoder;
use Symfony\Component\JsonEncoder\Mapping\Encode\AttributePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\Encode\DateTimeTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\GenericTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Stream\BufferedStream;
use Symfony\Component\JsonEncoder\Stream\MemoryStream;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\ClassicDummy;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithAttributesUsingServices;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithFormatterAttributes;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNameAttributes;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithUnionProperties;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeContext\TypeContextFactory;
use Symfony\Component\TypeInfo\TypeResolver\StringTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;
use Symfony\Contracts\Service\ServiceLocatorTrait;

class JsonEncoderTest extends TestCase
{
    private string $cacheDir;
    private JsonEncoder $encoder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sprintf('%s/symfony_test', sys_get_temp_dir());
        $encoderCacheDir = $this->cacheDir.'/json_encoder/encoder';

        if (is_dir($encoderCacheDir)) {
            array_map('unlink', glob($encoderCacheDir.'/*'));
            rmdir($encoderCacheDir);
        }

        $typeResolver = TypeResolver::create();
        $propertyMetadataLoader = new GenericTypePropertyMetadataLoader(
            new DateTimeTypePropertyMetadataLoader(new AttributePropertyMetadataLoader(
                new PropertyMetadataLoader($typeResolver),
                $typeResolver,
            )),
            new TypeContextFactory(new StringTypeResolver()),
        );

        $this->encoder = new JsonEncoder($propertyMetadataLoader, $this->cacheDir);
    }

    public function testReturnTraversableStringableEncoded()
    {
        $this->assertSame(['true'], iterator_to_array($this->encoder->encode(true)));
        $this->assertSame('true', (string) $this->encoder->encode(true));
    }

    public function testReturnEmptyWhenUsingStream()
    {
        $encoded = $this->encoder->encode(true, ['stream' => $stream = new MemoryStream()]);
        $this->assertEmpty(iterator_to_array($encoded));
    }

    public function testEncodeScalar()
    {
        $this->assertEncoded('null', null);
        $this->assertEncoded('true', true);
        $this->assertEncoded('[{"foo":1,"bar":2},{"foo":3}]', [['foo' => 1, 'bar' => 2], ['foo' => 3]]);
        $this->assertEncoded('{"foo":"bar"}', (object) ['foo' => 'bar']);
        $this->assertEncoded('1', DummyBackedEnum::ONE);
    }

    public function testEncodeUnion()
    {
        $this->assertEncoded(
            '[1,true,["foo","bar"]]',
            [DummyBackedEnum::ONE, true, ['foo', 'bar']],
            Type::list(Type::union(Type::enum(DummyBackedEnum::class), Type::bool(), Type::list(Type::string()))),
        );

        $dummy = new DummyWithUnionProperties();
        $dummy->value = DummyBackedEnum::ONE;
        $this->assertEncoded('{"value":1}', $dummy);

        $dummy->value = 'foo';
        $this->assertEncoded('{"value":"foo"}', $dummy);

        $dummy->value = null;
        $this->assertEncoded('{"value":null}', $dummy);
    }

    public function testEncodeObject()
    {
        $dummy = new ClassicDummy();
        $dummy->id = 10;
        $dummy->name = 'dummy name';

        $this->assertEncoded('{"id":10,"name":"dummy name"}', $dummy);
    }

    public function testEncodeObjectWithEncodedName()
    {
        $dummy = new DummyWithNameAttributes();
        $dummy->id = 10;
        $dummy->name = 'dummy name';

        $this->assertEncoded('{"@id":10,"name":"dummy name"}', $dummy);
    }

    public function testEncodeObjectWithEncodeFormatter()
    {
        $dummy = new DummyWithFormatterAttributes();
        $dummy->id = 10;
        $dummy->name = 'dummy name';

        $this->assertEncoded('{"id":"20","name":"DUMMY NAME"}', $dummy);
    }

    public function testEncodeObjectWithRuntimeServices()
    {
        $runtimeServices = new class([sprintf('%s::autowireAttribute[service]', DummyWithAttributesUsingServices::class) => fn () => fn (string $s) => strtoupper($s)]) implements ContainerInterface {
            use ServiceLocatorTrait;
        };

        $typeResolver = TypeResolver::create();
        $propertyMetadataLoader = new AttributePropertyMetadataLoader(new PropertyMetadataLoader($typeResolver), $typeResolver);
        $encoder = new JsonEncoder($propertyMetadataLoader, $this->cacheDir, $runtimeServices);

        $dummy = new DummyWithAttributesUsingServices();

        $this->assertSame('{"one":"one","two":"USELESS","three":"three"}', (string) $encoder->encode($dummy));

        $encoder->encode($dummy, ['stream' => $stream = new MemoryStream()]);
        $stream->rewind();
        $this->assertSame('{"one":"one","two":"USELESS","three":"three"}', $stream->read());
    }

    public function testCreateCacheFile()
    {
        $encoderCacheDir = $this->cacheDir.'/json_encoder/encoder';

        $this->encoder->encode(true);

        $this->assertFileExists($encoderCacheDir);
        $this->assertCount(1, glob($encoderCacheDir.'/*'));
    }

    public function testCreateCacheFileOnlyIfNotExists()
    {
        $encoderCacheDir = $this->cacheDir.'/json_encoder/encoder';

        if (!file_exists($encoderCacheDir)) {
            mkdir($encoderCacheDir, recursive: true);
        }

        file_put_contents(
            sprintf('%s%s%s.json.%s.php', $encoderCacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', (string) Type::bool()), EncodeAs::STRING->value),
            '<?php return static function ($data): \Traversable { yield "CACHED"; };'
        );

        $this->assertSame('CACHED', (string) $this->encoder->encode(true));
    }

    public function testRecreateCacheFileIfForceGeneration()
    {
        $encoderCacheDir = $this->cacheDir.'/json_encoder/encoder';

        if (!file_exists($encoderCacheDir)) {
            mkdir($encoderCacheDir, recursive: true);
        }

        file_put_contents(
            sprintf('%s%s%s.json.%s.php', $encoderCacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', (string) Type::bool()), EncodeAs::STRING->value),
            '<?php return static function ($data): \Traversable { yield "CACHED"; };'
        );

        $this->assertSame('true', (string) $this->encoder->encode(true, ['force_generation' => true]));
    }

    private function assertEncoded(string $encoded, mixed $decoded, Type $type = null): void
    {
        $this->assertSame($encoded, (string) $this->encoder->encode($decoded));

        $this->encoder->encode($decoded, ['stream' => $stream = new MemoryStream(), 'type' => $type]);
        $stream->rewind();
        $this->assertSame($encoded, (string) $stream);

        $this->encoder->encode($decoded, ['stream' => $stream = new BufferedStream(), 'type' => $type]);
        $stream->rewind();
        $this->assertSame($encoded, (string) $stream);
    }
}
