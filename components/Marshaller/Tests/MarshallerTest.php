<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Hook;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\HookOption;
use Symfony\Component\Marshaller\Context\Option\TypeOption;
use Symfony\Component\Marshaller\Marshaller;
use Symfony\Component\Marshaller\NativeContext\FormatterAttributeNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\HookNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\NameAttributeNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\TypeFormatterNativeContextBuilder;
use Symfony\Component\Marshaller\Output\MemoryStreamOutput;
use Symfony\Component\Marshaller\Tests\Fixtures\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\DummyWithFormatterAttributes;
use Symfony\Component\Marshaller\Tests\Fixtures\DummyWithNameAttributes;
use Symfony\Component\Marshaller\Tests\Fixtures\DummyWithQuotes;
use Symfony\Component\Marshaller\Type\PhpstanTypeExtractor;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;

final class MarshallerTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sys_get_temp_dir().'/symfony_marshaller';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    /**
     * @dataProvider marshalGenerateDataProvider
     *
     * @param list<string> $expectedLines
     */
    public function testMarshalGenerate(array $expectedLines, string $type, ?Context $context): void
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());

        $marshalGenerateContextBuilders = [
            new HookNativeContextBuilder(),
            new TypeFormatterNativeContextBuilder(),
            new NameAttributeNativeContextBuilder(),
            new FormatterAttributeNativeContextBuilder(),
        ];

        $lines = explode("\n", (new Marshaller($typeExtractor, $marshalGenerateContextBuilders, $this->cacheDir))->generate($type, 'json', $context));
        array_pop($lines);

        $this->assertSame($expectedLines, $lines);
    }

    /**
     * @return iterable<array{0: list<string>, 1: string, 2: Context}
     */
    public function marshalGenerateDataProvider(): iterable
    {
        yield [[
            '<?php',
            '',
            '/**',
            ' * @param int $data',
            ' * @param resource $resource',
            ' */',
            'return static function (mixed $data, $resource, array $context): void {',
            '    \fwrite($resource, $data);',
            '};',
        ], 'int', null];

        yield [[
            '<?php',
            '',
            '/**',
            ' * @param Symfony\Component\Marshaller\Tests\Fixtures\DummyWithNameAttributes $data',
            ' * @param resource $resource',
            ' */',
            'return static function (mixed $data, $resource, array $context): void {',
            '    $object_0 = ($data);',
            '    \fwrite($resource, "{\"@id\":");',
            '    \fwrite($resource, $object_0->id);',
            '    \fwrite($resource, ",\"name\":\"");',
            '    \fwrite($resource, \addcslashes($object_0->name, "\\"\\\\"));',
            '    \fwrite($resource, "\"}");',
            '};',
        ], DummyWithNameAttributes::class, null];

        yield [[
            '<?php',
            '',
            '/**',
            ' * @param Symfony\Component\Marshaller\Tests\Fixtures\DummyWithFormatterAttributes $data',
            ' * @param resource $resource',
            ' */',
            'return static function (mixed $data, $resource, array $context): void {',
            '    $object_0 = ($data);',
            '    \fwrite($resource, "{\"id\":\"");',
            '    \fwrite($resource, \addcslashes(Symfony\Component\Marshaller\Tests\Fixtures\DummyWithFormatterAttributes::doubleAndCastToString($object_0->id, $context), "\\"\\\\"));',
            '    \fwrite($resource, "\",\"name\":\"");',
            '    \fwrite($resource, \addcslashes($object_0->name, "\\"\\\\"));',
            '    \fwrite($resource, "\"}");',
            '};',
        ], DummyWithFormatterAttributes::class, null];
    }

    /**
     * @dataProvider marshalDataProvider
     *
     * @param list<string> $expectedLines
     */
    public function testMarshal(mixed $expectedDecodedData, mixed $data, ?Context $context): void
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());

        $marshalGenerateContextBuilders = [
            new HookNativeContextBuilder(),
            new TypeFormatterNativeContextBuilder(),
            new NameAttributeNativeContextBuilder(),
            new FormatterAttributeNativeContextBuilder(),
        ];

        (new Marshaller($typeExtractor, $marshalGenerateContextBuilders, $this->cacheDir))->marshal($data, 'json', $output = new MemoryStreamOutput(), $context);

        $this->assertSame($expectedDecodedData, json_decode((string) $output, true));
    }

    /**
     * @return iterable<array{0: array<string, mixed>, 1: mixed, 2: Context}
     */
    public function marshalDataProvider(): iterable
    {
        yield [null, null, null];
        yield [1, 1, null];
        yield ['1', 1, new Context(new TypeOption('string'))];
        yield [['id' => 1, 'name' => 'dummy'], new ClassicDummy(), null];
        yield [['@id' => 1, 'name' => 'dummy'], new DummyWithNameAttributes(), null];
        yield [['id' => '2', 'name' => 'dummy'], new DummyWithFormatterAttributes(), null];
        yield [['"name"' => '"quoted" dummy'], new DummyWithQuotes(), null];
        yield [['foo' => '1', 'name' => 'dummy'], new ClassicDummy(), new Context(new HookOption([
            sprintf('%s::$id', ClassicDummy::class) => static function (\ReflectionProperty $property, string $accessor, array $context): array {
                return [
                    'name' => 'foo',
                    'type' => 'string',
                    'accessor' => $accessor,
                    'context' => $context,
                ];
            },
        ]))];
    }
}
