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
use Symfony\Component\JsonEncoder\Decode\DecodeFrom;
use Symfony\Component\JsonEncoder\JsonDecoder;
use Symfony\Component\JsonEncoder\Stream\MemoryStream;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Denormalizer\BooleanStringDenormalizer;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Denormalizer\DivideStringAndCastToIntDenormalizer;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\ClassicDummy;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNameAttributes;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNormalizerAttributes;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNullableProperties;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithPhpDoc;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;

class JsonDecoderTest extends TestCase
{
    private string $decodersDir;
    private string $lazyGhostsDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decodersDir = sprintf('%s/symfony_json_encoder_test/decoder', sys_get_temp_dir());
        $this->lazyGhostsDir = sprintf('%s/symfony_json_encoder_test/lazy_ghost', sys_get_temp_dir());

        if (is_dir($this->decodersDir)) {
            array_map('unlink', glob($this->decodersDir.'/*'));
            rmdir($this->decodersDir);
        }

        if (is_dir($this->lazyGhostsDir)) {
            array_map('unlink', glob($this->lazyGhostsDir.'/*'));
            rmdir($this->lazyGhostsDir);
        }
    }

    public function testDecodeScalar()
    {
        $decoder = JsonDecoder::create(decodersDir: $this->decodersDir, lazyGhostsDir: $this->lazyGhostsDir);

        $this->assertDecoded($decoder, null, 'null', Type::nullable(Type::int()));
        $this->assertDecoded($decoder, true, 'true', Type::bool());
        $this->assertDecoded($decoder, [['foo' => 1, 'bar' => 2], ['foo' => 3]], '[{"foo": 1, "bar": 2}, {"foo": 3}]', Type::builtin(TypeIdentifier::ARRAY));
        $this->assertDecoded($decoder, [['foo' => 1, 'bar' => 2], ['foo' => 3]], '[{"foo": 1, "bar": 2}, {"foo": 3}]', Type::builtin(TypeIdentifier::ITERABLE));
        $this->assertDecoded($decoder, (object) ['foo' => 'bar'], '{"foo": "bar"}', Type::object());
        $this->assertDecoded($decoder, DummyBackedEnum::ONE, '1', Type::enum(DummyBackedEnum::class, Type::string()));
    }

    public function testDecodeCollection()
    {
        $decoder = JsonDecoder::create(decodersDir: $this->decodersDir, lazyGhostsDir: $this->lazyGhostsDir);

        $this->assertDecoded($decoder, [['foo' => 1, 'bar' => 2], ['foo' => 3]], '[{"foo": 1, "bar": 2}, {"foo": 3}]', Type::list(Type::dict(Type::int())));
        $this->assertDecoded($decoder, function (mixed $decoded) {
            $this->assertIsIterable($decoded);
            $array = [];
            foreach ($decoded as $item) {
                $array[] = iterator_to_array($item);
            }

            $this->assertSame([['foo' => 1, 'bar' => 2], ['foo' => 3]], $array);
        }, '[{"foo": 1, "bar": 2}, {"foo": 3}]', Type::iterable(Type::iterable(Type::int()), Type::int(), asList: true));
    }

    public function testDecodeObject()
    {
        $decoder = JsonDecoder::create(decodersDir: $this->decodersDir, lazyGhostsDir: $this->lazyGhostsDir);

        $this->assertDecoded($decoder, function (mixed $decoded) {
            $this->assertInstanceOf(ClassicDummy::class, $decoded);
            $this->assertSame(10, $decoded->id);
            $this->assertSame('dummy name', $decoded->name);
        }, '{"id": 10, "name": "dummy name"}', Type::object(ClassicDummy::class));
    }

    public function testDecodeObjectWithEncodedName()
    {
        $decoder = JsonDecoder::create(decodersDir: $this->decodersDir, lazyGhostsDir: $this->lazyGhostsDir);

        $this->assertDecoded($decoder, function (mixed $decoded) {
            $this->assertInstanceOf(DummyWithNameAttributes::class, $decoded);
            $this->assertSame(10, $decoded->id);
        }, '{"@id": 10}', Type::object(DummyWithNameAttributes::class));
    }

    public function testDecodeObjectWithDenormalizer()
    {
        $decoder = JsonDecoder::create(
            denormalizers: [
                BooleanStringDenormalizer::class => new BooleanStringDenormalizer(),
                DivideStringAndCastToIntDenormalizer::class => new DivideStringAndCastToIntDenormalizer(),
            ],
            decodersDir: $this->decodersDir,
            lazyGhostsDir: $this->lazyGhostsDir,
        );

        $this->assertDecoded($decoder, function (mixed $decoded) {
            $this->assertInstanceOf(DummyWithNormalizerAttributes::class, $decoded);
            $this->assertSame(10, $decoded->id);
            $this->assertTrue($decoded->active);
        }, '{"id": "20", "name": "DUMMY NAME", "active": "true"}', Type::object(DummyWithNormalizerAttributes::class), ['scale' => 1]);
    }

    public function testDecodeObjectWithPhpDoc()
    {
        $decoder = JsonDecoder::create(decodersDir: $this->decodersDir, lazyGhostsDir: $this->lazyGhostsDir);

        $this->assertDecoded($decoder, function (mixed $decoded) {
            $this->assertInstanceOf(DummyWithPhpDoc::class, $decoded);
            $this->assertIsArray($decoded->arrayOfDummies);
            $this->assertContainsOnlyInstancesOf(DummyWithNameAttributes::class, $decoded->arrayOfDummies);
            $this->assertArrayHasKey('key', $decoded->arrayOfDummies);
        }, '{"arrayOfDummies":{"key":{"@id":10,"name":"dummy"}}}', Type::object(DummyWithPhpDoc::class));
    }

    public function testDecodeObjectWithNullableProperties()
    {
        $decoder = JsonDecoder::create(decodersDir: $this->decodersDir, lazyGhostsDir: $this->lazyGhostsDir);

        $this->assertDecoded($decoder, function (mixed $decoded) {
            $this->assertInstanceOf(DummyWithNullableProperties::class, $decoded);
            $this->assertNull($decoded->name);
            $this->assertNull($decoded->enum);
        }, '{"name":null,"enum":null}', Type::object(DummyWithNullableProperties::class));
    }

    public function testCreateDecoderFile()
    {
        $decoder = JsonDecoder::create(decodersDir: $this->decodersDir, lazyGhostsDir: $this->lazyGhostsDir);

        $decoder->decode('true', Type::bool());

        $this->assertFileExists($this->decodersDir);
        $this->assertCount(1, glob($this->decodersDir.'/*'));
    }

    public function testCreateDecoderFileOnlyIfNotExists()
    {
        $decoder = JsonDecoder::create(decodersDir: $this->decodersDir, lazyGhostsDir: $this->lazyGhostsDir);

        if (!file_exists($this->decodersDir)) {
            mkdir($this->decodersDir, recursive: true);
        }

        file_put_contents(
            sprintf('%s%s%s.json.%s.php', $this->decodersDir, \DIRECTORY_SEPARATOR, hash('xxh128', (string) Type::bool()), DecodeFrom::STRING->value),
            '<?php return static function () { return "CACHED"; };'
        );

        $this->assertSame('CACHED', $decoder->decode('true', Type::bool()));
    }

    public function testRecreateDecoderFileIfForceGeneration()
    {
        $decoder = JsonDecoder::create(decodersDir: $this->decodersDir, lazyGhostsDir: $this->lazyGhostsDir);

        if (!file_exists($this->decodersDir)) {
            mkdir($this->decodersDir, recursive: true);
        }

        file_put_contents(
            sprintf('%s%s%s.json.%s.php', $this->decodersDir, \DIRECTORY_SEPARATOR, hash('xxh128', (string) Type::bool()), DecodeFrom::STRING->value),
            '<?php return static function () { return "CACHED"; };'
        );

        $this->assertTrue($decoder->decode('true', Type::bool(), ['force_generation' => true]));
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

    private function assertDecoded(JsonDecoder $decoder, mixed $decodedOrAssert, string $encoded, Type $type, array $config = []): void
    {
        $assert = \is_callable($decodedOrAssert, syntax_only: true) ? $decodedOrAssert : fn (mixed $decoded) => $this->assertEquals($decodedOrAssert, $decoded);

        $assert($decoder->decode($encoded, $type, $config));

        $stringable = new class($encoded) implements \Stringable {
            public function __construct(private string $string)
            {
            }

            public function __toString(): string
            {
                return $this->string;
            }
        };
        $assert($decoder->decode($stringable, $type, $config));

        $traversable = new \ArrayIterator(str_split($encoded, 2));
        $assert($decoder->decode($traversable, $type, $config));

        $stream = new MemoryStream();
        $stream->write($encoded);
        $stream->rewind();
        $assert($decoder->decode($stream, $type, $config));
    }
}
