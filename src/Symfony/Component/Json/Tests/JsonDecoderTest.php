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
use Symfony\Component\Encoder\Instantiator\Instantiator;
use Symfony\Component\Encoder\Instantiator\LazyInstantiator;
use Symfony\Component\Encoder\Mapping\Decode\AttributePropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\Decode\DateTimeTypePropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\GenericTypePropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\Encoder\Stream\MemoryStream;
use Symfony\Component\Json\JsonDecoder;
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
    private JsonDecoder $decoder;

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

        $typeResolver = self::getTypeResolver();
        $propertyMetadataLoader = new GenericTypePropertyMetadataLoader(
            new DateTimeTypePropertyMetadataLoader(new AttributePropertyMetadataLoader(
                new PropertyMetadataLoader($typeResolver),
                $typeResolver,
            )),
            $typeResolver,
        );

        $dataModeBuilder = new DataModelBuilder($propertyMetadataLoader);
        $template = new Template($dataModeBuilder, $this->templateCacheDir);

        $this->decoder = new JsonDecoder($template, new Instantiator(), new LazyInstantiator($this->lazyObjectCacheDir), $this->templateCacheDir);
    }

    public function testDecodeScalar()
    {
        $this->assertDecoded(null, 'null', Type::int(nullable: true));
        $this->assertDecoded(true, 'true', Type::bool());
        $this->assertDecoded([['foo' => 1, 'bar' => 2], ['foo' => 3]], '[{"foo": 1, "bar": 2}, {"foo": 3}]', Type::array());
        $this->assertDecoded([['foo' => 1, 'bar' => 2], ['foo' => 3]], '[{"foo": 1, "bar": 2}, {"foo": 3}]', Type::iterable());
        $this->assertDecoded((object) ['foo' => 'bar'], '{"foo": "bar"}', Type::object());
        $this->assertDecoded(DummyBackedEnum::ONE, '1', Type::enum(DummyBackedEnum::class, Type::string()));
    }

    public function testDecodeCollection()
    {
        $this->assertDecoded([['foo' => 1, 'bar' => 2], ['foo' => 3]], '[{"foo": 1, "bar": 2}, {"foo": 3}]', Type::list(Type::dict(Type::int())));
        $this->assertDecoded(function (mixed $decoded) {
            $this->assertIsIterable($decoded);
            $array = [];
            foreach ($decoded as $item) {
                $array[] = iterator_to_array($item);
            }

            $this->assertSame([['foo' => 1, 'bar' => 2], ['foo' => 3]], $array);
        }, '[{"foo": 1, "bar": 2}, {"foo": 3}]', Type::iterableList(Type::iterableDict(Type::int())));
    }

    public function testDecodeObject()
    {
        $this->assertDecoded(function (mixed $decoded) {
            $this->assertInstanceOf(ClassicDummy::class, $decoded);
            $this->assertSame(10, $decoded->id);
            $this->assertSame('dummy name', $decoded->name);
        }, '{"id": 10, "name": "dummy name"}', Type::object(ClassicDummy::class));
    }

    public function testDecodeObjectWithEncodedName()
    {
        $this->assertDecoded(function (mixed $decoded) {
            $this->assertInstanceOf(DummyWithNameAttributes::class, $decoded);
            $this->assertSame(10, $decoded->id);
        }, '{"@id": 10}', Type::object(DummyWithNameAttributes::class));
    }

    public function testDecodeObjectWithDecodeFormatter()
    {
        $this->assertDecoded(function (mixed $decoded) {
            $this->assertInstanceOf(DummyWithFormatterAttributes::class, $decoded);
            $this->assertSame(10, $decoded->id);
        }, '{"id": "20"}', Type::object(DummyWithFormatterAttributes::class));
    }

    public function testDecodeObjectWithRuntimeServices()
    {
        $typeResolver = self::getTypeResolver();
        $propertyMetadataLoader = new AttributePropertyMetadataLoader(new PropertyMetadataLoader($typeResolver), $typeResolver);

        $service = new JsonDecoder(
            new Template(new DataModelBuilder($propertyMetadataLoader), $this->templateCacheDir),
            new Instantiator(),
            new LazyInstantiator($this->lazyObjectCacheDir),
            $this->templateCacheDir,
        );

        $runtimeServices = new class([sprintf('%s::serviceAndConfig[service]', DummyWithAttributesUsingServices::class) => fn () => $service]) implements ContainerInterface {
            use ServiceLocatorTrait;
        };

        $dataModelBuilder = new DataModelBuilder($propertyMetadataLoader, $runtimeServices);

        $decoder = new JsonDecoder(
            new Template($dataModelBuilder, $this->templateCacheDir),
            new Instantiator(),
            new LazyInstantiator($this->lazyObjectCacheDir),
            $this->templateCacheDir,
            $runtimeServices,
        );

        $encoded = '{"one":"\"one\"","two":"two","three":"three"}';

        $decoded = $decoder->decode($encoded, Type::object(DummyWithAttributesUsingServices::class));
        $this->assertInstanceOf(DummyWithAttributesUsingServices::class, $decoded);
        $this->assertSame('one', $decoded->one);

        $traversable = new \ArrayIterator(str_split($encoded, 2));
        $this->assertInstanceOf(DummyWithAttributesUsingServices::class, $decoded);
        $this->assertSame('one', $decoded->one);

        $stream = new MemoryStream();
        $stream->write($encoded);
        $stream->rewind();
        $this->assertInstanceOf(DummyWithAttributesUsingServices::class, $decoded);
        $this->assertSame('one', $decoded->one);
    }

    public function testCreateCacheFile()
    {
        $this->decoder->decode('true', Type::bool());

        $this->assertFileExists($this->templateCacheDir);
        $this->assertCount(1, glob($this->templateCacheDir.'/*'));
    }

    public function testCreateCacheFileOnlyIfNotExists()
    {
        $template = new Template(
            new DataModelBuilder(new PropertyMetadataLoader(self::getTypeResolver())),
            $this->templateCacheDir,
        );

        if (!file_exists($this->templateCacheDir)) {
            mkdir($this->templateCacheDir, recursive: true);
        }

        file_put_contents($template->getPath(Type::bool(), Template::DECODE_FROM_STRING), '<?php return static function () { return "CACHED"; };');

        $this->assertSame('CACHED', $this->decoder->decode('true', Type::bool()));
    }

    public function testRecreateCacheFileIfForceGenerateTemplate()
    {
        $template = new Template(
            new DataModelBuilder(new PropertyMetadataLoader(self::getTypeResolver())),
            $this->templateCacheDir,
        );

        if (!file_exists($this->templateCacheDir)) {
            mkdir($this->templateCacheDir, recursive: true);
        }

        file_put_contents($template->getPath(Type::bool(), Template::DECODE_FROM_STRING), '<?php return static function () { return "CACHED"; };');

        $this->assertTrue($this->decoder->decode('true', Type::bool(), ['force_generate_template' => true]));
    }

    private function decode(string $input, Type $type, JsonDecoder $decoder, array $config = []): mixed
    {
        if ($decoder instanceof JsonDecoder) {
            return $decoder->decode($input, $type, $config);
        }

        $inputStream = (new MemoryStream());
        fwrite($inputStream->getResource(), $input);
        rewind($inputStream->getResource());

        return $decoder->decode($inputStream, $type, $config);
    }

    private function assertDecoded(mixed $decodedOrAssert, string $encoded, Type $type): void
    {
        $assert = \is_callable($decodedOrAssert, syntax_only: true) ? $decodedOrAssert : fn (mixed $decoded) => $this->assertEquals($decodedOrAssert, $decoded);

        $assert($this->decoder->decode($encoded, $type));

        $stringable = new class($encoded) implements \Stringable {
            public function __construct(private string $string)
            {
            }

            public function __toString(): string
            {
                return $this->string;
            }
        };
        $assert($this->decoder->decode($stringable, $type));

        $traversable = new \ArrayIterator(str_split($encoded, 2));
        $assert($this->decoder->decode($traversable, $type));

        $stream = new MemoryStream();
        $stream->write($encoded);
        $stream->rewind();
        $assert($this->decoder->decode($stream, $type));
    }
}
