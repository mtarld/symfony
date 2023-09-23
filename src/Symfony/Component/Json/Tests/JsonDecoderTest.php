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
use Symfony\Component\Encoder\DataModel\Decode\DataModelBuilder;
use Symfony\Component\Encoder\Instantiator\EagerInstantiator;
use Symfony\Component\Encoder\Instantiator\LazyInstantiator;
use Symfony\Component\Encoder\Mapping\Decode\AttributePropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\Decode\DateTimeTypePropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\GenericTypePropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\Encoder\Stream\MemoryStream;
use Symfony\Component\Json\JsonDecoder;
use Symfony\Component\Json\JsonStreamingDecoder;
use Symfony\Component\Json\Template\Decode\Template;
use Symfony\Component\Json\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\Json\Tests\Fixtures\Model\ClassicDummy;
use Symfony\Component\Json\Tests\Fixtures\Model\DummyWithAttributesUsingServices;
use Symfony\Component\Json\Tests\Fixtures\Model\DummyWithFormatterAttributes;
use Symfony\Component\Json\Tests\Fixtures\Model\DummyWithNameAttributes;
use Symfony\Component\TypeInfo\Type;
use Symfony\Contracts\Service\ServiceLocatorTrait;

class JsonDecoderTest extends TestCase
{
    use TypeResolverAwareTrait;

    private string $templateCacheDir;
    private string $lazyObjectCacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templateCacheDir = sprintf('%s/symfony_json_template', sys_get_temp_dir());
        $this->lazyObjectCacheDir = sprintf('%s/symfony_encoder_lazy_ghost', sys_get_temp_dir());

        if (is_dir($this->templateCacheDir)) {
            array_map('unlink', glob($this->templateCacheDir.'/*'));
            rmdir($this->templateCacheDir);
        }

        if (is_dir($this->lazyObjectCacheDir)) {
            array_map('unlink', glob($this->lazyObjectCacheDir.'/*'));
            rmdir($this->lazyObjectCacheDir);
        }
    }

    /**
     * @dataProvider decoders
     */
    public function testDecodeScalar(JsonDecoder|JsonStreamingDecoder $decoder)
    {
        $this->assertNull($this->decode('null', Type::int(nullable: true), $decoder));
        $this->assertTrue($this->decode('true', Type::bool(), $decoder));

        $this->assertSame(
            [['foo' => 1, 'bar' => 2], ['foo' => 3]],
            $this->decode('[{"foo": 1, "bar": 2}, {"foo": 3}]', Type::array(), $decoder),
        );
        $this->assertSame(
            [['foo' => 1, 'bar' => 2], ['foo' => 3]],
            $this->decode('[{"foo": 1, "bar": 2}, {"foo": 3}]', Type::iterable(), $decoder),
        );
        $this->assertEquals(
            (object) ['foo' => 'bar'],
            $this->decode('{"foo": "bar"}', Type::object(), $decoder),
        );
        $this->assertEquals(
            DummyBackedEnum::ONE,
            $this->decode('1', Type::enum(DummyBackedEnum::class, Type::string()), $decoder),
        );
    }

    /**
     * @dataProvider decoders
     */
    public function testDecodeCollection(JsonDecoder|JsonStreamingDecoder $decoder)
    {
        $this->assertSame(
            [['foo' => 1, 'bar' => 2], ['foo' => 3]],
            $this->decode('[{"foo": 1, "bar": 2}, {"foo": 3}]', Type::list(Type::dict(Type::int())), $decoder),
        );

        $iterable = $this->decode('[{"foo": 1, "bar": 2}, {"foo": 3}]', Type::iterableList(Type::iterableDict(Type::int())), $decoder);
        $this->assertIsIterable($iterable);
        $array = [];
        foreach ($iterable as $item) {
            $array[] = iterator_to_array($item);
        }

        $this->assertSame([['foo' => 1, 'bar' => 2], ['foo' => 3]], $array);
    }

    /**
     * @dataProvider decoders
     */
    public function testDecodeObject(JsonDecoder|JsonStreamingDecoder $decoder)
    {
        $decoded = $this->decode('{"id": 10, "name": "dummy name"}', Type::object(ClassicDummy::class), $decoder);

        $this->assertInstanceOf(ClassicDummy::class, $decoded);
        $this->assertSame(10, $decoded->id);
        $this->assertSame('dummy name', $decoded->name);
    }

    /**
     * @dataProvider decoders
     */
    public function testDecodeObjectWithEncodedName(JsonDecoder|JsonStreamingDecoder $decoder)
    {
        $decoded = $this->decode('{"@id": 10}', Type::object(DummyWithNameAttributes::class), $decoder);

        $this->assertInstanceOf(DummyWithNameAttributes::class, $decoded);
        $this->assertSame(10, $decoded->id);
    }

    /**
     * @dataProvider decoders
     */
    public function testDecodeObjectWithDecodeFormatter(JsonDecoder|JsonStreamingDecoder $decoder)
    {
        $decoded = $this->decode('{"id": "20"}', Type::object(DummyWithFormatterAttributes::class), $decoder);

        $this->assertInstanceOf(DummyWithFormatterAttributes::class, $decoded);
        $this->assertSame(10, $decoded->id);
    }

    /**
     * @dataProvider decoders
     */
    public function testDecodeObjectWithRuntimeServices(JsonDecoder|JsonStreamingDecoder $decoder)
    {
        $typeResolver = self::getTypeResolver();
        $service = new JsonDecoder(
            new Template(new DataModelBuilder(new PropertyMetadataLoader($typeResolver)), 'cache'),
            new EagerInstantiator(),
            'cache',
        );

        $runtimeServices = new class([sprintf('%s::serviceAndConfig[service]', DummyWithAttributesUsingServices::class) => fn () => $service]) implements ContainerInterface {
            use ServiceLocatorTrait;
        };

        $propertyMetadataLoader = new AttributePropertyMetadataLoader(new PropertyMetadataLoader($typeResolver), $typeResolver);

        $dataModeBuilder = new DataModelBuilder($propertyMetadataLoader, $runtimeServices);
        $template = new Template($dataModeBuilder, $this->templateCacheDir);

        $dummy = new DummyWithAttributesUsingServices();

        $decoder = new JsonDecoder($template, new EagerInstantiator(), $this->templateCacheDir, $runtimeServices);
        $decoded = $this->decode('{"one":"\"one\"","two":"two","three":"three"}', Type::object(DummyWithAttributesUsingServices::class), $decoder);

        $this->assertInstanceOf(DummyWithAttributesUsingServices::class, $decoded);
        $this->assertSame('one', $decoded->one);

        $decoder = new JsonStreamingDecoder($template, new LazyInstantiator($this->lazyObjectCacheDir), $this->templateCacheDir, $runtimeServices);
        $decoded = $this->decode('{"one":"\"one\"","two":"two","three":"three"}', Type::object(DummyWithAttributesUsingServices::class), $decoder);

        $this->assertInstanceOf(DummyWithAttributesUsingServices::class, $decoded);
        $this->assertSame('one', $decoded->one);
    }

    /**
     * @dataProvider decoders
     */
    public function testCreateCacheFile(JsonDecoder|JsonStreamingDecoder $decoder)
    {
        $this->decode('true', Type::bool(), $decoder);

        $this->assertFileExists($this->templateCacheDir);
        $this->assertCount(1, glob($this->templateCacheDir.'/*'));
    }

    /**
     * @dataProvider decoders
     */
    public function testCreateCacheFileOnlyIfNotExists(JsonDecoder|JsonStreamingDecoder $decoder)
    {
        $template = new Template(
            new DataModelBuilder(new PropertyMetadataLoader(self::getTypeResolver())),
            $this->templateCacheDir,
        );

        if (!file_exists($this->templateCacheDir)) {
            mkdir($this->templateCacheDir, recursive: true);
        }

        file_put_contents(
            $template->getPath(Type::bool(), forStream: $decoder instanceof JsonStreamingDecoder),
            '<?php return static function () { return "CACHED"; };',
        );

        $this->assertSame('CACHED', $this->decode('true', Type::bool(), $decoder));
    }

    /**
     * @dataProvider decoders
     */
    public function testRecreateCacheFileIfForceGenerateTemplate(JsonDecoder|JsonStreamingDecoder $decoder)
    {
        $template = new Template(
            new DataModelBuilder(new PropertyMetadataLoader(self::getTypeResolver())),
            $this->templateCacheDir,
        );

        if (!file_exists($this->templateCacheDir)) {
            mkdir($this->templateCacheDir, recursive: true);
        }

        file_put_contents(
            $template->getPath(Type::bool(), forStream: $decoder instanceof JsonStreamingDecoder),
            '<?php return static function () { return "CACHED"; };',
        );

        $this->assertTrue($this->decode(
            'true',
            Type::bool(),
            $decoder,
            ['force_generate_template' => true],
        ));
    }

    private function decode(string $input, Type $type, JsonDecoder|JsonStreamingDecoder $decoder, array $config = []): mixed
    {
        if ($decoder instanceof JsonDecoder) {
            return $decoder->decode($input, $type, $config);
        }

        $inputStream = (new MemoryStream());
        fwrite($inputStream->getResource(), $input);
        rewind($inputStream->getResource());

        return $decoder->decode($inputStream, $type, $config);
    }

    /**
     * @return iterable<array{0: JsonDecode|JsonStreamingDecoder}>
     */
    public function decoders(): iterable
    {
        $templateCacheDir = sprintf('%s/symfony_json_template', sys_get_temp_dir());
        $lazyObjectCacheDir = sprintf('%s/symfony_encoder_lazy_ghost', sys_get_temp_dir());
        $typeResolver = self::getTypeResolver();

        $propertyMetadataLoader = new GenericTypePropertyMetadataLoader(
            new DateTimeTypePropertyMetadataLoader(new AttributePropertyMetadataLoader(
                new PropertyMetadataLoader($typeResolver),
                $typeResolver,
            )),
            $typeResolver,
        );

        $dataModeBuilder = new DataModelBuilder($propertyMetadataLoader);
        $template = new Template($dataModeBuilder, $templateCacheDir);

        yield [new JsonDecoder($template, new EagerInstantiator(), $templateCacheDir)];
        yield [new JsonStreamingDecoder($template, new LazyInstantiator($lazyObjectCacheDir), $templateCacheDir)];
    }
}
