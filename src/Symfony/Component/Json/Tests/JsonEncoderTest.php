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
use Symfony\Component\Encoder\DataModel\Encode\DataModelBuilder;
use Symfony\Component\Encoder\Mapping\Encode\AttributePropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\Encode\DateTimeTypePropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\GenericTypePropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\Json\JsonEncoder;
use Symfony\Component\Json\JsonStreamingEncoder;
use Symfony\Component\Json\Template\Encode\Template;
use Symfony\Component\Json\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\Json\Tests\Fixtures\Model\ClassicDummy;
use Symfony\Component\Json\Tests\Fixtures\Model\DummyWithAttributesUsingServices;
use Symfony\Component\Json\Tests\Fixtures\Model\DummyWithFormatterAttributes;
use Symfony\Component\Json\Tests\Fixtures\Model\DummyWithNameAttributes;
use Symfony\Component\TypeInfo\Type;
use Symfony\Contracts\Service\ServiceLocatorTrait;

class JsonEncoderTest extends TestCase
{
    use TypeResolverAwareTrait;

    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sprintf('%s/symfony_json_template', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    /**
     * @dataProvider encoders
     */
    public function testEncodeScalar(JsonEncoder|JsonStreamingEncoder $encoder)
    {
        $this->assertSame('null', (string) $encoder->encode(null));
        $this->assertSame('true', (string) $encoder->encode(true));
        $this->assertSame('[{"foo":1,"bar":2},{"foo":3}]', (string) $encoder->encode([['foo' => 1, 'bar' => 2], ['foo' => 3]]));
        $this->assertSame('{"foo":"bar"}', (string) $encoder->encode((object) ['foo' => 'bar']));
        $this->assertSame('1', (string) $encoder->encode(DummyBackedEnum::ONE));
    }

    /**
     * @dataProvider encoders
     */
    public function testEncodeObject(JsonEncoder|JsonStreamingEncoder $encoder)
    {
        $dummy = new ClassicDummy();
        $dummy->id = 10;
        $dummy->name = 'dummy name';

        $this->assertSame('{"id":10,"name":"dummy name"}', (string) $encoder->encode($dummy));
    }

    /**
     * @dataProvider encoders
     */
    public function testEncodeObjectWithEncodedName(JsonEncoder|JsonStreamingEncoder $encoder)
    {
        $dummy = new DummyWithNameAttributes();
        $dummy->id = 10;
        $dummy->name = 'dummy name';

        $this->assertSame('{"@id":10,"name":"dummy name"}', (string) $encoder->encode($dummy));
    }

    /**
     * @dataProvider encoders
     */
    public function testEncodeObjectWithEncodeFormatter(JsonEncoder|JsonStreamingEncoder $encoder)
    {
        $dummy = new DummyWithFormatterAttributes();
        $dummy->id = 10;
        $dummy->name = 'dummy name';

        $this->assertSame('{"id":"20","name":"dummy name"}', (string) $encoder->encode($dummy));
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

        $dummy = new DummyWithAttributesUsingServices();

        $encoder = new JsonEncoder($template, $this->cacheDir, $runtimeServices);
        $this->assertSame('{"one":"one","two":"USELESS","three":"three"}', (string) $encoder->encode($dummy));

        $encoder = new JsonStreamingEncoder($template, $this->cacheDir, $runtimeServices);
        $this->assertSame('{"one":"one","two":"USELESS","three":"three"}', (string) $encoder->encode($dummy));
    }

    /**
     * @dataProvider encoders
     */
    public function testCreateCacheFile(JsonEncoder|JsonStreamingEncoder $encoder)
    {
        $encoder->encode(true);

        $this->assertFileExists($this->cacheDir);
        $this->assertCount(1, glob($this->cacheDir.'/*'));
    }

    /**
     * @dataProvider encoders
     */
    public function testCreateCacheFileOnlyIfNotExists(JsonEncoder|JsonStreamingEncoder $encoder)
    {
        $template = new Template(
            new DataModelBuilder(new PropertyMetadataLoader(self::getTypeResolver())),
            $this->cacheDir,
        );
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, recursive: true);
        }

        file_put_contents(
            $template->getPath(Type::bool(), forStream: $encoder instanceof JsonStreamingEncoder),
            '<?php return static function ($data, $resource) { \fwrite($resource, "CACHED"); };',
        );

        $this->assertSame('CACHED', (string) $encoder->encode(true));
    }

    /**
     * @dataProvider encoders
     */
    public function testRecreateCacheFileIfForceGenerateTemplate(JsonEncoder|JsonStreamingEncoder $encoder)
    {
        $template = new Template(
            new DataModelBuilder(new PropertyMetadataLoader(self::getTypeResolver())),
            $this->cacheDir,
        );
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, recursive: true);
        }

        file_put_contents(
            $template->getPath(Type::bool(), forStream: $encoder instanceof JsonStreamingEncoder),
            '<?php return static function ($data, $resource) { \fwrite($resource, "CACHED"); };',
        );

        $this->assertSame('true', (string) $encoder->encode(true, ['force_generate_template' => true]));
    }

    /**
     * @return iterable<array{0: JsonEncoder|JsonStreamingEncoder}>
     */
    public function encoders(): iterable
    {
        $cacheDir = sprintf('%s/symfony_json_template', sys_get_temp_dir());
        $typeResolver = self::getTypeResolver();

        $propertyMetadataLoader = new GenericTypePropertyMetadataLoader(
            new DateTimeTypePropertyMetadataLoader(new AttributePropertyMetadataLoader(
                new PropertyMetadataLoader($typeResolver),
                $typeResolver,
            )),
            $typeResolver,
        );

        $dataModeBuilder = new DataModelBuilder($propertyMetadataLoader);
        $template = new Template($dataModeBuilder, $cacheDir);

        yield [new JsonEncoder($template, $cacheDir)];
        yield [new JsonStreamingEncoder($template, $cacheDir)];
    }
}
