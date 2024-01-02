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
use Symfony\Component\JsonEncoder\DataModel\Encode\DataModelBuilder;
use Symfony\Component\JsonEncoder\Encode\EncodeAs;
use Symfony\Component\JsonEncoder\Encode\EncoderGenerator;
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

        $this->cacheDir = sprintf('%s/symfony_json_encoder_encoder', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }

        $typeResolver = TypeResolver::create();
        $propertyMetadataLoader = new GenericTypePropertyMetadataLoader(
            new DateTimeTypePropertyMetadataLoader(new AttributePropertyMetadataLoader(
                new PropertyMetadataLoader($typeResolver),
                $typeResolver,
            )),
            new TypeContextFactory(new StringTypeResolver()),
        );

        $dataModeBuilder = new DataModelBuilder($propertyMetadataLoader);
        $generator = new EncoderGenerator($dataModeBuilder, $this->cacheDir);

        $this->encoder = new JsonEncoder($generator);
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

        $dataModeBuilder = new DataModelBuilder($propertyMetadataLoader, $runtimeServices);
        $generator = new EncoderGenerator($dataModeBuilder, $this->cacheDir);

        $encoder = new JsonEncoder($generator, $runtimeServices);

        $dummy = new DummyWithAttributesUsingServices();

        $this->assertSame('{"one":"one","two":"USELESS","three":"three"}', (string) $encoder->encode($dummy));

        $encoder->encode($dummy, ['stream' => $stream = new MemoryStream()]);
        $stream->rewind();
        $this->assertSame('{"one":"one","two":"USELESS","three":"three"}', $stream->read());
    }

    public function testCreateCacheFile()
    {
        $this->encoder->encode(true);

        $this->assertFileExists($this->cacheDir);
        $this->assertCount(1, glob($this->cacheDir.'/*'));
    }

    public function testCreateCacheFileOnlyIfNotExists()
    {
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, recursive: true);
        }

        file_put_contents(
            sprintf('%s%s%s.json.%s.php', $this->cacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', (string) Type::bool()), EncodeAs::STRING->value),
            '<?php return static function ($data): \Traversable { yield "CACHED"; };'
        );

        $this->assertSame('CACHED', (string) $this->encoder->encode(true));
    }

    public function testRecreateCacheFileIfForceGeneration()
    {
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, recursive: true);
        }

        file_put_contents(
            sprintf('%s%s%s.json.%s.php', $this->cacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', (string) Type::bool()), EncodeAs::STRING->value),
            '<?php return static function ($data): \Traversable { yield "CACHED"; };'
        );

        $this->assertSame('true', (string) $this->encoder->encode(true, ['force_generation' => true]));
    }

    private function assertEncoded(string $encoded, mixed $decoded): void
    {
        $this->assertSame($encoded, (string) $this->encoder->encode($decoded));

        $this->encoder->encode($decoded, ['stream' => $stream = new MemoryStream()]);
        $stream->rewind();
        $this->assertSame($encoded, (string) $stream);

        $this->encoder->encode($decoded, ['stream' => $stream = new BufferedStream()]);
        $stream->rewind();
        $this->assertSame($encoded, (string) $stream);
    }
}
