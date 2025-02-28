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
use Symfony\Component\JsonEncoder\Exception\MaxDepthException;
use Symfony\Component\JsonEncoder\JsonEncoder;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\ClassicDummy;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithDateTimes;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNameAttributes;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNullableProperties;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithPhpDoc;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithUnionAndValueTransformer;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithUnionProperties;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithValueTransformerAttributes;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\SelfReferencingDummy;
use Symfony\Component\JsonEncoder\Tests\Fixtures\ValueTransformer\BooleanToStringValueTransformer;
use Symfony\Component\JsonEncoder\Tests\Fixtures\ValueTransformer\DoubleIntAndCastToStringValueTransformer;
use Symfony\Component\JsonEncoder\ValueTransformer\DateTimeToStringValueTransformer;
use Symfony\Component\JsonEncoder\ValueTransformer\ValueTransformerInterface;
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

    public function testEncodeScalar()
    {
        $this->assertEncoded('null', null, Type::null());
        $this->assertEncoded('true', true, Type::bool());
        $this->assertEncoded('[{"foo":1,"bar":2},{"foo":3}]', [['foo' => 1, 'bar' => 2], ['foo' => 3]], Type::list());
        $this->assertEncoded('{"foo":"bar"}', (object) ['foo' => 'bar'], Type::object());
        $this->assertEncoded('1', DummyBackedEnum::ONE, Type::enum(DummyBackedEnum::class));
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
        $this->assertEncoded('{"value":1}', $dummy, Type::object(DummyWithUnionProperties::class));

        $dummy->value = 'foo';
        $this->assertEncoded('{"value":"foo"}', $dummy, Type::object(DummyWithUnionProperties::class));

        $dummy->value = null;
        $this->assertEncoded('{"value":null}', $dummy, Type::object(DummyWithUnionProperties::class));
    }

    public function testEncodeCollection()
    {
        $this->assertEncoded(
            '{"0":{"id":1,"name":"dummy"},"1":{"id":1,"name":"dummy"}}',
            [new ClassicDummy(), new ClassicDummy()],
            Type::array(Type::object(ClassicDummy::class)),
        );

        $this->assertEncoded(
            '[{"id":1,"name":"dummy"},{"id":1,"name":"dummy"}]',
            [new ClassicDummy(), new ClassicDummy()],
            Type::list(Type::object(ClassicDummy::class)),
        );

        $this->assertEncoded(
            '{"0":{"id":1,"name":"dummy"},"1":{"id":1,"name":"dummy"}}',
            new \ArrayObject([new ClassicDummy(), new ClassicDummy()]),
            Type::iterable(Type::object(ClassicDummy::class)),
        );

        $this->assertEncoded(
            '{"0":{"id":1,"name":"dummy"},"1":{"id":1,"name":"dummy"}}',
            new \ArrayObject([new ClassicDummy(), new ClassicDummy()]),
            Type::iterable(Type::object(ClassicDummy::class), Type::int()),
        );
    }

    public function testEncodeObject()
    {
        $dummy = new ClassicDummy();
        $dummy->id = 10;
        $dummy->name = 'dummy name';

        $this->assertEncoded('{"id":10,"name":"dummy name"}', $dummy, Type::object(ClassicDummy::class));
    }

    public function testEncodeObjectWithEncodedName()
    {
        $dummy = new DummyWithNameAttributes();
        $dummy->id = 10;
        $dummy->name = 'dummy name';

        $this->assertEncoded('{"@id":10,"name":"dummy name"}', $dummy, Type::object(DummyWithNameAttributes::class));
    }

    public function testEncodeObjectWithValueTransformer()
    {
        $dummy = new DummyWithValueTransformerAttributes();
        $dummy->id = 10;
        $dummy->active = true;

        $this->assertEncoded(
            '{"id":"20","active":"true","name":"dummy","range":"10..20"}',
            $dummy,
            Type::object(DummyWithValueTransformerAttributes::class),
            options: ['scale' => 1],
            valueTransformers: [
                BooleanToStringValueTransformer::class => new BooleanToStringValueTransformer(),
                DoubleIntAndCastToStringValueTransformer::class => new DoubleIntAndCastToStringValueTransformer(),
            ],
        );
    }

    public function testEncodeObjectWithPhpDoc()
    {
        $dummy = new DummyWithPhpDoc();
        $dummy->arrayOfDummies = ['key' => new DummyWithNameAttributes()];

        $this->assertEncoded('{"arrayOfDummies":{"key":{"@id":1,"name":"dummy"}},"array":[]}', $dummy, Type::object(DummyWithPhpDoc::class));
    }

    public function testEncodeObjectWithNullableProperties()
    {
        $dummy = new DummyWithNullableProperties();

        $this->assertEncoded('{"name":null,"enum":null}', $dummy, Type::object(DummyWithNullableProperties::class));
    }

    public function testEncodeObjectWithDateTimes()
    {
        $dummy = new DummyWithDateTimes();
        $dummy->interface = new \DateTimeImmutable('2024-11-20');
        $dummy->immutable = new \DateTimeImmutable('2025-11-20');

        $this->assertEncoded(
            '{"interface":"2024-11-20","immutable":"2025-11-20"}',
            $dummy,
            Type::object(DummyWithDateTimes::class),
            options: [DateTimeToStringValueTransformer::FORMAT_KEY => 'Y-m-d'],
        );
    }

    public function testEncodeObjectWithUnionAndValueTransformer()
    {
        $dummy = new DummyWithUnionAndValueTransformer();
        $dummy->foo = new \DateTimeImmutable('2024-11-20');

        $this->assertEncoded(
            '{"foo":"2024-11-20"}',
            $dummy,
            Type::object(DummyWithUnionAndValueTransformer::class),
            options: [DateTimeToStringValueTransformer::FORMAT_KEY => 'Y-m-d'],
        );
    }

    public function testThrowWhenMaxDepthIsReached()
    {
        $encoder = JsonEncoder::create(encodersDir: $this->encodersDir);

        $dummy = new SelfReferencingDummy();
        for ($i = 0; $i < 512; ++$i) {
            $tmp = new SelfReferencingDummy();
            $tmp->self = $dummy;

            $dummy = $tmp;
        }

        $this->expectException(MaxDepthException::class);
        $this->expectExceptionMessage('Max depth of 512 has been reached.');

        (string) $encoder->encode($dummy, Type::object(SelfReferencingDummy::class));
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
            \sprintf('%s%s%s.json.php', $this->encodersDir, \DIRECTORY_SEPARATOR, hash('xxh128', (string) Type::bool())),
            '<?php return static function ($data): \Traversable { yield "CACHED"; };'
        );

        $this->assertSame('CACHED', (string) $encoder->encode(true, Type::bool()));
    }

    /**
     * @param array<string, mixed>                     $options
     * @param array<string, ValueTransformerInterface> $valueTransformers
     */
    private function assertEncoded(string $expected, mixed $data, Type $type, array $options = [], array $valueTransformers = []): void
    {
        $encoder = JsonEncoder::create(encodersDir: $this->encodersDir, valueTransformers: $valueTransformers);
        $this->assertSame($expected, (string) $encoder->encode($data, $type, $options));
    }
}
