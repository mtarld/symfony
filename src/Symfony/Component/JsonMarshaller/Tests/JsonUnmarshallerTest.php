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
use Symfony\Component\JsonMarshaller\JsonUnmarshaller;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithAttributesUsingServices;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithFormatterAttributes;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithNameAttributes;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\JsonMarshaller\Type\PhpstanTypeExtractor;
use Symfony\Component\JsonMarshaller\Type\ReflectionTypeExtractor;
use Symfony\Component\JsonMarshaller\Type\Type;
use Symfony\Component\JsonMarshaller\Type\TypeExtractorInterface;
use Symfony\Component\JsonMarshaller\Unmarshal\DataModel\DataModelBuilder;
use Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\EagerInstantiator;
use Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\InstantiatorInterface;
use Symfony\Component\JsonMarshaller\Unmarshal\Mapping\AttributePropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Unmarshal\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Unmarshal\Mapping\TypePropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Unmarshal\Template\Template;
use Symfony\Contracts\Service\ServiceLocatorTrait;

class JsonUnmarshallerTest extends TestCase
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

    public function testUnmarshalWithStreamInput()
    {
        $input = fopen('php://memory', 'w+');
        fwrite($input, 'true');
        rewind($input);

        $this->assertTrue($this->unmarshaller()->unmarshal($input, Type::bool()));
    }

    public function testUnmarshalScalar()
    {
        $this->assertNull($this->unmarshaller()->unmarshal('null', Type::int(nullable: true)));
        $this->assertTrue($this->unmarshaller()->unmarshal('true', Type::bool()));

        $this->assertSame(
            [['foo' => 1, 'bar' => 2], ['foo' => 3]],
            $this->unmarshaller()->unmarshal('[{"foo": 1, "bar": 2}, {"foo": 3}]', Type::array()),
        );
        $this->assertSame(
            [['foo' => 1, 'bar' => 2], ['foo' => 3]],
            $this->unmarshaller()->unmarshal('[{"foo": 1, "bar": 2}, {"foo": 3}]', Type::iterable()),
        );
        $this->assertEquals(
            (object) ['foo' => 'bar'],
            $this->unmarshaller()->unmarshal('{"foo": "bar"}', Type::object()),
        );
        $this->assertEquals(
            DummyBackedEnum::ONE,
            $this->unmarshaller()->unmarshal('1', Type::enum(DummyBackedEnum::class)),
        );
    }

    public function testUnmarshalCollection()
    {
        $this->assertSame(
            [['foo' => 1, 'bar' => 2], ['foo' => 3]],
            $this->unmarshaller()->unmarshal('[{"foo": 1, "bar": 2}, {"foo": 3}]', Type::list(Type::dict(Type::int()))),
        );

        $iterable = $this->unmarshaller()->unmarshal('[{"foo": 1, "bar": 2}, {"foo": 3}]', Type::iterableList(Type::iterableDict(Type::int())));
        $this->assertIsIterable($iterable);
        $array = [];
        foreach ($iterable as $item) {
            $array[] = iterator_to_array($item);
        }

        $this->assertSame([['foo' => 1, 'bar' => 2], ['foo' => 3]], $array);
    }

    public function testUnmarshalObject()
    {
        $dummy = new ClassicDummy();
        $dummy->id = 10;
        $dummy->name = 'dummy name';

        $this->assertEquals(
            $dummy,
            $this->unmarshaller()->unmarshal('{"id": 10, "name": "dummy name"}', Type::class(ClassicDummy::class)),
        );
    }

    public function testUnmarshalObjectWithMarshalledName()
    {
        $dummy = new DummyWithNameAttributes();
        $dummy->id = 10;

        $this->assertEquals(
            $dummy,
            $this->unmarshaller()->unmarshal('{"@id": 10}', Type::class(DummyWithNameAttributes::class)),
        );
    }

    public function testUnmarshalObjectWithUnmarshalFormatter()
    {
        $dummy = new DummyWithFormatterAttributes();
        $dummy->id = 10;

        $this->assertEquals(
            $dummy,
            $this->unmarshaller()->unmarshal('{"id": "20"}', Type::class(DummyWithFormatterAttributes::class)),
        );
    }

    public function testUnmarshalObjectWithRuntimeServices()
    {
        $dummy = new DummyWithAttributesUsingServices();
        $dummy->one = 'bool';

        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractTypeFromProperty')->willReturn(Type::bool());

        $services = [
            sprintf('%s::serviceAndConfig[service]', DummyWithAttributesUsingServices::class) => fn () => $typeExtractor,
        ];

        $this->assertEquals(
            $dummy,
            $this->unmarshaller($services)->unmarshal('{"one":"one","two":"two","three":"three"}', Type::class(DummyWithAttributesUsingServices::class)),
        );
    }

    public function testUnmarshalObjectWithCustomInstantiator()
    {
        $instantiated = new ClassicDummy();
        $instantiated->id = 69004;

        $instantiator = $this->createStub(InstantiatorInterface::class);
        $instantiator->method('instantiate')->willReturn($instantiated);

        $this->assertSame(
            $instantiated,
            $this->unmarshaller()->unmarshal('{"id": 10, "name": "dummy name"}', Type::class(ClassicDummy::class), ['instantiator' => $instantiator]),
        );
    }

    public function testCreateCacheFile()
    {
        $this->unmarshaller()->unmarshal('true', Type::bool());

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
        file_put_contents($cacheFilename, '<?php return static function () { return "CACHED"; };');

        $this->assertSame('CACHED', $this->unmarshaller()->unmarshal('true', Type::bool()));
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
        file_put_contents($cacheFilename, '<?php return static function () { return "CACHED"; };');

        $this->assertTrue($this->unmarshaller()->unmarshal(
            'true',
            Type::bool(),
            ['force_generate_template' => true],
        ));
    }

    /**
     * @param array<string, mixed>|null $runtimeServices
     */
    private function unmarshaller(array $runtimeServices = null): JsonUnmarshaller
    {
        $runtimeServicesLocator = null !== $runtimeServices ? new class($runtimeServices) implements ContainerInterface {
            use ServiceLocatorTrait;
        } : null;

        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());
        $instantiator = new EagerInstantiator();

        $propertyMetadataLoader = new TypePropertyMetadataLoader(
            new AttributePropertyMetadataLoader(new PropertyMetadataLoader($typeExtractor), $typeExtractor),
            $typeExtractor,
        );

        $dataModeBuilder = new DataModelBuilder($propertyMetadataLoader, $runtimeServicesLocator);
        $template = new Template($dataModeBuilder, $this->cacheDir);

        return new JsonUnmarshaller($template, $instantiator, $this->cacheDir, false, $runtimeServicesLocator);
    }
}
