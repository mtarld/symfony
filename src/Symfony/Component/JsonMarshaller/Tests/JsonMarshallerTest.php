<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\JsonMarshaller\JsonMarshaller;
use Symfony\Component\JsonMarshaller\Marshal\DataModel\DataModelBuilder;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\AttributePropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\TypePropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Marshal\Template\Template;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithAttributesUsingServices;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithFormatterAttributes;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithNameAttributes;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\JsonMarshaller\Type\PhpstanTypeExtractor;
use Symfony\Component\JsonMarshaller\Type\ReflectionTypeExtractor;
use Symfony\Component\JsonMarshaller\Type\Type;
use Symfony\Contracts\Service\ServiceLocatorTrait;

class JsonMarshallerTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sprintf('%s/symfony_json_marshaller_template', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    public function testMarshalWithResourceOutput()
    {
        $output = fopen('php://memory', 'w+');

        $this->marshaller()->marshal(true, [], $output);
        rewind($output);

        $this->assertSame('true', stream_get_contents($output));
    }

    public function testMarshalScalar()
    {
        $this->assertSame('null', $this->marshaller()->marshal(null));
        $this->assertSame('true', $this->marshaller()->marshal(true));
        $this->assertSame(
            '[{"foo":1,"bar":2},{"foo":3}]',
            $this->marshaller()->marshal([['foo' => 1, 'bar' => 2], ['foo' => 3]]),
        );
        $this->assertEquals(
            '{"foo":"bar"}',
            $this->marshaller()->marshal((object) ['foo' => 'bar']),
        );
        $this->assertEquals(
            '1',
            $this->marshaller()->marshal(DummyBackedEnum::ONE),
        );
    }

    public function testMarshalObject()
    {
        $dummy = new ClassicDummy();
        $dummy->id = 10;
        $dummy->name = 'dummy name';

        $this->assertEquals('{"id":10,"name":"dummy name"}', $this->marshaller()->marshal($dummy));
    }

    public function testMarshalObjectWithMarshalledName()
    {
        $dummy = new DummyWithNameAttributes();
        $dummy->id = 10;
        $dummy->name = 'dummy name';

        $this->assertEquals(
            '{"@id":10,"name":"dummy name"}',
            $this->marshaller()->marshal($dummy),
        );
    }

    public function testMarshalObjectWithMarshalFormatter()
    {
        $dummy = new DummyWithFormatterAttributes();
        $dummy->id = 10;
        $dummy->name = 'dummy name';

        $this->assertEquals(
            '{"id":"20","name":"dummy name"}',
            $this->marshaller()->marshal($dummy),
        );
    }

    public function testMarshalObjectWithRuntimeServices()
    {
        $dummy = new DummyWithAttributesUsingServices();

        $services = [
            sprintf('%s::autowireAttribute[service]', DummyWithAttributesUsingServices::class) => fn () => fn (string $s) => strtoupper($s),
        ];

        $this->assertEquals(
            '{"one":"one","two":"USELESS","three":"three"}',
            $this->marshaller($services)->marshal($dummy),
        );
    }

    public function testCreateCacheFile()
    {
        $this->marshaller()->marshal(true);

        $this->assertFileExists($this->cacheDir);
        $this->assertCount(1, glob($this->cacheDir.'/*'));
    }

    public function testCreateCacheFileOnlyIfNotExists()
    {
        $template = new Template(
            new DataModelBuilder(new PropertyMetadataLoader(new ReflectionTypeExtractor())),
            $this->cacheDir,
        );
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, recursive: true);
        }

        $cacheFilename = $template->path(Type::bool(), false);
        file_put_contents($cacheFilename, '<?php return static function ($data, $resource) { \fwrite($resource, "CACHED"); };');

        $this->assertSame('CACHED', $this->marshaller()->marshal(true));
    }

    public function testRecreateCacheFileIfForceGenerateTemplate()
    {
        $template = new Template(
            new DataModelBuilder(new PropertyMetadataLoader(new ReflectionTypeExtractor())),
            $this->cacheDir,
        );
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, recursive: true);
        }

        $cacheFilename = $template->path(Type::bool(), false);
        file_put_contents($cacheFilename, '<?php return static function ($data, $resource) { \fwrite($resource, "CACHED"); };');

        $this->assertSame('true', $this->marshaller()->marshal(true, ['force_generate_template' => true]));
    }

    /**
     * @param array<string, mixed>|null $runtimeServices
     */
    private function marshaller(array $runtimeServices = null): JsonMarshaller
    {
        $runtimeServicesLocator = null !== $runtimeServices ? new class($runtimeServices) implements ContainerInterface {
            use ServiceLocatorTrait;
        } : null;

        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());

        $propertyMetadataLoader = new TypePropertyMetadataLoader(
            new AttributePropertyMetadataLoader(new PropertyMetadataLoader($typeExtractor), $typeExtractor),
            $typeExtractor,
        );

        $dataModeBuilder = new DataModelBuilder($propertyMetadataLoader, $runtimeServicesLocator);
        $template = new Template($dataModeBuilder, $this->cacheDir);

        return new JsonMarshaller($template, $this->cacheDir, $runtimeServicesLocator);
    }
}
