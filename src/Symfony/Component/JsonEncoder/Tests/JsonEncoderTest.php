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
use Symfony\Component\JsonEncoder\Encode\EncodeAs;
use Symfony\Component\JsonEncoder\JsonEncoder;
use Symfony\Component\JsonEncoder\Stream\BufferedStream;
use Symfony\Component\JsonEncoder\Stream\MemoryStream;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\ClassicDummy;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNameAttributes;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNormalizerAttributes;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNullableProperties;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithPhpDoc;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithUnionProperties;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Normalizer\BooleanStringNormalizer;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Normalizer\DoubleIntAndCastToStringNormalizer;
use Symfony\Component\TypeInfo\Type;

class JsonEncoderTest extends TestCase
{
    private string $encodersDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->encodersDir = \sprintf('%s/symfony_json_encoder_test/encoder', sys_get_temp_dir());

        if (is_dir($this->encodersDir)) {
            array_map('unlink', glob($this->encodersDir.'/*'));
            rmdir($this->encodersDir);
        }
    }

    public function testReturnTraversableStringableEncoded()
    {
        $encoder = JsonEncoder::create(encodersDir: $this->encodersDir);

        $this->assertSame(['true'], iterator_to_array($encoder->encode(true, Type::bool())));
        $this->assertSame('true', (string) $encoder->encode(true, Type::bool()));
    }

    public function testReturnEmptyWhenUsingStream()
    {
        $encoder = JsonEncoder::create(encodersDir: $this->encodersDir);

        $encoded = $encoder->encode(true, Type::bool(), ['stream' => $stream = new MemoryStream()]);
        $this->assertEmpty(iterator_to_array($encoded));
    }

    public function testEncodeScalar()
    {
        $encoder = JsonEncoder::create(encodersDir: $this->encodersDir);

        $this->assertEncoded($encoder, 'null', null, Type::null());
        $this->assertEncoded($encoder, 'true', true, Type::bool());
        $this->assertEncoded($encoder, '[{"foo":1,"bar":2},{"foo":3}]', [['foo' => 1, 'bar' => 2], ['foo' => 3]], Type::array());
        $this->assertEncoded($encoder, '{"foo":"bar"}', (object) ['foo' => 'bar'], Type::object());
        $this->assertEncoded($encoder, '1', DummyBackedEnum::ONE, Type::enum(DummyBackedEnum::class));
    }

    public function testEncodeUnion()
    {
        $encoder = JsonEncoder::create(encodersDir: $this->encodersDir);

        $this->assertEncoded(
            $encoder,
            '[1,true,["foo","bar"]]',
            [DummyBackedEnum::ONE, true, ['foo', 'bar']],
            Type::list(Type::union(Type::enum(DummyBackedEnum::class), Type::bool(), Type::list(Type::string()))),
        );

        $dummy = new DummyWithUnionProperties();
        $dummy->value = DummyBackedEnum::ONE;
        $this->assertEncoded($encoder, '{"value":1}', $dummy, Type::object(DummyWithUnionProperties::class));

        $dummy->value = 'foo';
        $this->assertEncoded($encoder, '{"value":"foo"}', $dummy, Type::object(DummyWithUnionProperties::class));

        $dummy->value = null;
        $this->assertEncoded($encoder, '{"value":null}', $dummy, Type::object(DummyWithUnionProperties::class));
    }

    public function testEncodeObject()
    {
        $encoder = JsonEncoder::create(encodersDir: $this->encodersDir);

        $dummy = new ClassicDummy();
        $dummy->id = 10;
        $dummy->name = 'dummy name';

        $this->assertEncoded($encoder, '{"id":10,"name":"dummy name"}', $dummy, Type::object(ClassicDummy::class));
    }

    public function testEncodeObjectWithEncodedName()
    {
        $encoder = JsonEncoder::create(encodersDir: $this->encodersDir);

        $dummy = new DummyWithNameAttributes();
        $dummy->id = 10;
        $dummy->name = 'dummy name';

        $this->assertEncoded($encoder, '{"@id":10,"name":"dummy name"}', $dummy, Type::object(DummyWithNameAttributes::class));
    }

    public function testEncodeObjectWithNormalizer()
    {
        $encoder = JsonEncoder::create(
            normalizers: [
                BooleanStringNormalizer::class => new BooleanStringNormalizer(),
                DoubleIntAndCastToStringNormalizer::class => new DoubleIntAndCastToStringNormalizer(),
            ],
            encodersDir: $this->encodersDir,
        );

        $dummy = new DummyWithNormalizerAttributes();
        $dummy->id = 10;
        $dummy->active = true;

        $this->assertEncoded($encoder, '{"id":"20","active":"true"}', $dummy, Type::object(DummyWithNormalizerAttributes::class), ['scale' => 1]);
    }

    public function testEncodeObjectWithPhpDoc()
    {
        $encoder = JsonEncoder::create(encodersDir: $this->encodersDir);

        $dummy = new DummyWithPhpDoc();
        $dummy->arrayOfDummies = ['key' => new DummyWithNameAttributes()];

        $this->assertEncoded($encoder, '{"arrayOfDummies":{"key":{"@id":1,"name":"dummy"}},"array":[]}', $dummy, Type::object(DummyWithPhpDoc::class));
    }

    public function testEncodeObjectWithNullableProperties()
    {
        $encoder = JsonEncoder::create(encodersDir: $this->encodersDir);

        $dummy = new DummyWithNullableProperties();

        $this->assertEncoded($encoder, '{"name":null,"enum":null}', $dummy, Type::object(DummyWithNullableProperties::class));
    }

    public function testCreateEncoderFile()
    {
        $encoder = JsonEncoder::create(encodersDir: $this->encodersDir);

        $encoder->encode(true, Type::bool());

        $this->assertFileExists($this->encodersDir);
        $this->assertCount(1, glob($this->encodersDir.'/*'));
    }

    public function testCreateEncoderFileOnlyIfNotExists()
    {
        $encoder = JsonEncoder::create(encodersDir: $this->encodersDir);

        if (!file_exists($this->encodersDir)) {
            mkdir($this->encodersDir, recursive: true);
        }

        file_put_contents(
            \sprintf('%s%s%s.json.%s.php', $this->encodersDir, \DIRECTORY_SEPARATOR, hash('xxh128', (string) Type::bool()), EncodeAs::STRING->value),
            '<?php return static function ($data): \Traversable { yield "CACHED"; };'
        );

        $this->assertSame('CACHED', (string) $encoder->encode(true, Type::bool()));
    }

    public function testRecreateEncoderFileIfForceGeneration()
    {
        $encoder = JsonEncoder::create(encodersDir: $this->encodersDir);

        if (!file_exists($this->encodersDir)) {
            mkdir($this->encodersDir, recursive: true);
        }

        file_put_contents(
            \sprintf('%s%s%s.json.%s.php', $this->encodersDir, \DIRECTORY_SEPARATOR, hash('xxh128', (string) Type::bool()), EncodeAs::STRING->value),
            '<?php return static function ($data): \Traversable { yield "CACHED"; };'
        );

        $this->assertSame('true', (string) $encoder->encode(true, Type::bool(), ['force_generation' => true]));
    }

    private function assertEncoded(JsonEncoder $encoder, string $encoded, mixed $decoded, Type $type, array $config = []): void
    {
        $this->assertSame($encoded, (string) $encoder->encode($decoded, $type, $config));

        $encoder->encode($decoded, $type, ['stream' => $stream = new MemoryStream(), ...$config]);
        $stream->rewind();
        $this->assertSame($encoded, (string) $stream);

        $encoder->encode($decoded, $type, ['stream' => $stream = new BufferedStream(), ...$config]);
        $stream->rewind();
        $this->assertSame($encoded, (string) $stream);
    }
}
