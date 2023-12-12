<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Json\JsonEncoder;
use Symfony\Component\Json\Template\Encode\Template;
use Symfony\Component\Json\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\Json\Tests\Fixtures\Model\ClassicDummy;
use Symfony\Component\Json\Tests\Fixtures\Model\DummyWithAttributesUsingServices;
use Symfony\Component\Json\Tests\Fixtures\Model\DummyWithFormatterAttributes;
use Symfony\Component\Json\Tests\Fixtures\Model\DummyWithNameAttributes;
use Symfony\Component\JsonEncoder\DataModel\Encode\DataModelBuilder;
use Symfony\Component\JsonEncoder\Mapping\Encode\AttributePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\Encode\DateTimeTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\GenericTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Stream\BufferedStream;
use Symfony\Component\JsonEncoder\Stream\MemoryStream;
use Symfony\Component\TypeInfo\Type;
use Symfony\Contracts\Service\ServiceLocatorTrait;

class JsonEncoderTest extends TestCase
{
    use TypeResolverAwareTrait;

    private string $cacheDir;
    private JsonEncoder $encoder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sprintf('%s/symfony_json_template', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }

        $typeResolver = self::getTypeResolver();
        $propertyMetadataLoader = new GenericTypePropertyMetadataLoader(
            new DateTimeTypePropertyMetadataLoader(new AttributePropertyMetadataLoader(
                new PropertyMetadataLoader($typeResolver),
                $typeResolver,
            )),
            $typeResolver,
        );

        $dataModeBuilder = new DataModelBuilder($propertyMetadataLoader);
        $template = new Template($dataModeBuilder, $this->cacheDir);

        $this->encoder = new JsonEncoder($template, $this->cacheDir);
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

        $typeResolver = self::getTypeResolver();
        $propertyMetadataLoader = new AttributePropertyMetadataLoader(new PropertyMetadataLoader($typeResolver), $typeResolver);

        $dataModeBuilder = new DataModelBuilder($propertyMetadataLoader, $runtimeServices);
        $template = new Template($dataModeBuilder, $this->cacheDir);

        $encoder = new JsonEncoder($template, $this->cacheDir, $runtimeServices);

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
        $template = new Template(
            new DataModelBuilder(new PropertyMetadataLoader(self::getTypeResolver())),
            $this->cacheDir,
        );
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, recursive: true);
        }

        file_put_contents($template->getPath(Type::bool(), Template::ENCODE_TO_STRING), '<?php return static function ($data): \Traversable { yield "CACHED"; };');

        $this->assertSame('CACHED', (string) $this->encoder->encode(true));
    }

    public function testRecreateCacheFileIfForceGenerateTemplate()
    {
        $template = new Template(
            new DataModelBuilder(new PropertyMetadataLoader(self::getTypeResolver())),
            $this->cacheDir,
        );
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, recursive: true);
        }

        file_put_contents($template->getPath(Type::bool(), Template::ENCODE_TO_STRING), '<?php return static function ($data): \Traversable { yield "CACHED"; };');

        $this->assertSame('true', (string) $this->encoder->encode(true, ['force_generate_template' => true]));
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
