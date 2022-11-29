<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Hook;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\HookOption;
use Symfony\Component\Marshaller\Context\Option\TypeFormatterOption;
use Symfony\Component\Marshaller\Context\Option\TypeOption;
use Symfony\Component\Marshaller\Marshaller;
use Symfony\Component\Marshaller\NativeContext\FormatterAttributeNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\HookNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\NameAttributeNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\TypeFormatterNativeContextBuilder;
use Symfony\Component\Marshaller\Output\MemoryStreamOutput;
use Symfony\Component\Marshaller\Tests\Fixtures\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\DummyWithFormatterAttributes;
use Symfony\Component\Marshaller\Tests\Fixtures\DummyWithMethods;
use Symfony\Component\Marshaller\Tests\Fixtures\DummyWithNameAttributes;
use Symfony\Component\Marshaller\Type\PhpstanTypeExtractor;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;
use Symfony\Component\Marshaller\Type\TypeExtractorInterface;

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
     * @dataProvider marshalJsonGenerateDataProvider
     *
     * @param list<string> $expectedLines
     */
    public function testMarshalJsonGenerate(array $expectedLines, string $type, ?Context $context): void
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
    public function marshalJsonGenerateDataProvider(): iterable
    {
        yield [[
            '<?php',
            '',
            '/**',
            ' * @param int $data',
            ' * @param resource $resource',
            ' */',
            'return static function (mixed $data, $resource, array $context): void {',
            '    \fwrite($resource, "\"");',
            '    \fwrite($resource, \addcslashes(Symfony\Component\Marshaller\Tests\Fixtures\DummyWithMethods::doubleAndCastToString($data, $context), "\"\\\\"));',
            '    \fwrite($resource, "\"");',
            '};',
        ], 'int', new Context(new TypeFormatterOption(['int' => DummyWithMethods::doubleAndCastToString(...)]))];

        yield [[
            '<?php',
            '',
            '/**',
            ' * @param int $data',
            ' * @param resource $resource',
            ' */',
            'return static function (mixed $data, $resource, array $context): void {',
            '    \fwrite($resource, "\"");',
            '    \fwrite($resource, \addcslashes($foo, "\"\\\\"));',
            '    \fwrite($resource, "\"");',
            '};',
        ], 'int', new Context(new HookOption([
            'int' => static function (string $type, string $accessor, array $context): array {
                return [
                    'type' => 'string',
                    'accessor' => '$foo',
                    'context' => $context,
                ];
            },
        ]))];

        yield [[
            '<?php',
            '',
            '/**',
            ' * @param Symfony\Component\Marshaller\Tests\Fixtures\ClassicDummy $data',
            ' * @param resource $resource',
            ' */',
            'return static function (mixed $data, $resource, array $context): void {',
            '    $object_0 = $data;',
            '    \fwrite($resource, "{\"foo\":\"");',
            '    \fwrite($resource, \addcslashes($bar, "\\"\\\\"));',
            '    \fwrite($resource, "\",\"foo\":\"");',
            '    \fwrite($resource, \addcslashes($bar, "\\"\\\\"));',
            '    \fwrite($resource, "\"}");',
            '};',
        ], ClassicDummy::class, new Context(new HookOption([
            'property' => static function (\ReflectionProperty $property, string $accessor, array $context): array {
                return [
                    'name' => 'foo',
                    'type' => 'string',
                    'accessor' => '$bar',
                    'context' => $context,
                ];
            },
        ]))];

        yield [[
            '<?php',
            '',
            '/**',
            ' * @param Symfony\Component\Marshaller\Tests\Fixtures\DummyWithNameAttributes $data',
            ' * @param resource $resource',
            ' */',
            'return static function (mixed $data, $resource, array $context): void {',
            '    $object_0 = $data;',
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
            '    $object_0 = $data;',
            '    \fwrite($resource, "{\"id\":\"");',
            '    \fwrite($resource, \addcslashes(Symfony\Component\Marshaller\Tests\Fixtures\DummyWithFormatterAttributes::doubleAndCastToString($object_0->id, $context), "\\"\\\\"));',
            '    \fwrite($resource, "\",\"name\":\"");',
            '    \fwrite($resource, \addcslashes($object_0->name, "\\"\\\\"));',
            '    \fwrite($resource, "\"}");',
            '};',
        ], DummyWithFormatterAttributes::class, null];
    }

    public function testJsonMarshal(): void
    {
        $marshaller = new Marshaller($this->createStub(TypeExtractorInterface::class), [], $this->cacheDir);

        $marshaller->marshal(1, 'json', $output = new MemoryStreamOutput());
        $this->assertSame('1', (string) $output);

        $marshaller->marshal(1, 'json', $output = new MemoryStreamOutput(), new Context(new TypeOption('string')));
        $this->assertSame('"1"', (string) $output);
    }
}
