<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Tests\Fixtures\CircularReferencingDummyLeft;
use Symfony\Component\Marshaller\Tests\Fixtures\CircularReferencingDummyRight;
use Symfony\Component\Marshaller\Tests\Fixtures\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\SelfReferencingDummy;

use function Symfony\Component\Marshaller\Native\marshal_generate;

final class MarshalGenerateTest extends TestCase
{
    /**
     * @dataProvider generateJsonTemplateDataProvider
     *
     * @param list<string>         $expectedLines
     * @param array<string, mixed> $context
     */
    public function testGenerateJsonTemplate(array $expectedLines, string $type, array $context): void
    {
        $lines = explode("\n", marshal_generate($type, 'json', $context));
        array_pop($lines);

        $this->assertSame($expectedLines, $lines);
    }

    /**
     * @return iterable<array{0: list<string>, 1: string, 2: array<string, mixed>}
     */
    public function generateJsonTemplateDataProvider(): iterable
    {
        yield [[
            '<?php',
            '',
            '/**',
            ' * @param null $data',
            ' * @param resource $resource',
            ' */',
            'return static function (mixed $data, $resource, array $context): void {',
            '    \fwrite($resource, "null");',
            '};',
        ], 'null', []];

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
        ], 'int', []];

        yield [[
            '<?php',
            '',
            '/**',
            ' * @param string $data',
            ' * @param resource $resource',
            ' */',
            'return static function (mixed $data, $resource, array $context): void {',
            '    \fwrite($resource, "\"");',
            '    \fwrite($resource, \addcslashes($data, "\"\\\\"));',
            '    \fwrite($resource, "\"");',
            '};',
        ], 'string', []];

        yield [[
            '<?php',
            '',
            '/**',
            ' * @param bool $data',
            ' * @param resource $resource',
            ' */',
            'return static function (mixed $data, $resource, array $context): void {',
            '    \fwrite($resource, $data ? "true" : "false");',
            '};',
        ], 'bool', []];

        yield [[
            '<?php',
            '',
            '/**',
            ' * @param array<int, int> $data',
            ' * @param resource $resource',
            ' */',
            'return static function (mixed $data, $resource, array $context): void {',
            '    \fwrite($resource, "[");',
            '    $prefix_0 = "";',
            '    foreach ($data as $value_0) {',
            '        \fwrite($resource, $prefix_0);',
            '        \fwrite($resource, $value_0);',
            '        $prefix_0 = ",";',
            '    }',
            '    \fwrite($resource, "]");',
            '};',
        ], 'array<int, int>', []];

        yield [[
            '<?php',
            '',
            '/**',
            ' * @param array<string, int> $data',
            ' * @param resource $resource',
            ' */',
            'return static function (mixed $data, $resource, array $context): void {',
            '    \fwrite($resource, "{");',
            '    $prefix_0 = "";',
            '    foreach ($data as $key_0 => $value_0) {',
            '        \fwrite($resource, "{$prefix_0}\"");',
            '        \fwrite($resource, \addcslashes($key_0, "\"\\\\"));',
            '        \fwrite($resource, "\":");',
            '        \fwrite($resource, $value_0);',
            '        $prefix_0 = ",";',
            '    }',
            '    \fwrite($resource, "}");',
            '};',
        ], 'array<string, int>', []];

        yield [[
            '<?php',
            '',
            '/**',
            ' * @param Symfony\Component\Marshaller\Tests\Fixtures\ClassicDummy $data',
            ' * @param resource $resource',
            ' */',
            'return static function (mixed $data, $resource, array $context): void {',
            '    $object_0 = $data;',
            '    \fwrite($resource, "{\"id\":");',
            '    \fwrite($resource, $object_0->id);',
            '    \fwrite($resource, ",\"name\":\"");',
            '    \fwrite($resource, \addcslashes($object_0->name, "\\"\\\\"));',
            '    \fwrite($resource, "\"}");',
            '};',
        ], ClassicDummy::class, []];

        yield [[
            '<?php',
            '',
            '/**',
            ' * @param array<int, Symfony\Component\Marshaller\Tests\Fixtures\ClassicDummy> $data',
            ' * @param resource $resource',
            ' */',
            'return static function (mixed $data, $resource, array $context): void {',
            '    \fwrite($resource, "[");',
            '    $prefix_0 = "";',
            '    foreach ($data as $value_0) {',
            '        \fwrite($resource, $prefix_0);',
            '        $object_0 = $value_0;',
            '        \fwrite($resource, "{\"id\":");',
            '        \fwrite($resource, $object_0->id);',
            '        \fwrite($resource, ",\"name\":\"");',
            '        \fwrite($resource, \addcslashes($object_0->name, "\\"\\\\"));',
            '        \fwrite($resource, "\"}");',
            '        $prefix_0 = ",";',
            '    }',
            '    \fwrite($resource, "]");',
            '};',
        ], sprintf('array<int, %s>', ClassicDummy::class), []];

        yield [[
            '<?php',
            '',
            '/**',
            ' * @param ?int $data',
            ' * @param resource $resource',
            ' */',
            'return static function (mixed $data, $resource, array $context): void {',
            '    if ((null) === ($data)) {',
            '        \fwrite($resource, "null");',
            '    } else {',
            '        \fwrite($resource, $data);',
            '    }',
            '};',
        ], '?int', []];

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
        ], 'int', [
            'hooks' => [
                'int' => static function (string $type, string $accessor, array $context): array {
                    return [
                        'type' => 'string',
                        'accessor' => '$foo',
                        'context' => $context,
                    ];
                },
            ],
        ]];

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
            '    \fwrite($resource, "\",\"name\":\"");',
            '    \fwrite($resource, \addcslashes($object_0->name, "\\"\\\\"));',
            '    \fwrite($resource, "\"}");',
            '};',
        ], ClassicDummy::class, [
            'hooks' => [
                sprintf('%s::$id', ClassicDummy::class) => static function (\ReflectionProperty $property, string $accessor, array $context): array {
                    return [
                        'name' => 'foo',
                        'type' => 'string',
                        'accessor' => '$bar',
                        'context' => $context,
                    ];
                },
            ],
        ]];

        yield [[
            '<?php',
            '',
            '/**',
            ' * @param Symfony\Component\Marshaller\Tests\Fixtures\ClassicDummy $data',
            ' * @param resource $resource',
            ' */',
            'return static function (mixed $data, $resource, array $context): void {',
            '    $object_0 = $data;',
            '    \fwrite($resource, "{\"foo\":");',
            '    \fwrite($resource, $foo ? "true" : "false");',
            '    \fwrite($resource, ",\"name\":\"");',
            '    \fwrite($resource, \addcslashes($object_0->name, "\\"\\\\"));',
            '    \fwrite($resource, "\"}");',
            '};',
        ], ClassicDummy::class, [
            'hooks' => [
                'int' => static function (string $type, string $accessor, array $context): array {
                    return [
                        'type' => 'bool',
                        'accessor' => '$foo',
                        'context' => $context,
                    ];
                },
                sprintf('%s::$id', ClassicDummy::class) => static function (\ReflectionProperty $property, string $accessor, array $context): array {
                    return [
                        'name' => 'foo',
                        'type' => 'int',
                        'accessor' => '$bar',
                        'context' => $context,
                    ];
                },
            ],
        ]];
    }

    public function testThrowOnUnknownFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown "foo" format.');

        marshal_generate('int', 'foo');
    }

    /**
     * @dataProvider checkForCircularReferencesDataProvider
     */
    public function testCheckForCircularReferences(?string $expectedCircularClassName, string $type): void
    {
        if (null !== $expectedCircularClassName) {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage(sprintf('Circular reference detected on "%s"', $expectedCircularClassName));
        }

        marshal_generate($type, 'json');

        $this->addToAssertionCount(1);
    }

    /**
     * @return iterable<array{0: ?string, 1: string}>
     */
    public function checkForCircularReferencesDataProvider(): iterable
    {
        yield [null, ClassicDummy::class];
        yield [null, sprintf('array<int, %s>', ClassicDummy::class)];
        yield [null, sprintf('array<string, %s>', ClassicDummy::class)];
        yield [null, sprintf('%s|%1$s', ClassicDummy::class)];

        yield [SelfReferencingDummy::class, SelfReferencingDummy::class];
        yield [SelfReferencingDummy::class, sprintf('array<int, %s>', SelfReferencingDummy::class)];
        yield [SelfReferencingDummy::class, sprintf('array<string, %s>', SelfReferencingDummy::class)];
        yield [SelfReferencingDummy::class, sprintf('%s|%1$s', SelfReferencingDummy::class)];

        yield [CircularReferencingDummyLeft::class, CircularReferencingDummyLeft::class];
        yield [CircularReferencingDummyLeft::class, sprintf('array<int, %s>', CircularReferencingDummyLeft::class)];
        yield [CircularReferencingDummyLeft::class, sprintf('array<string, %s>', CircularReferencingDummyLeft::class)];
        yield [CircularReferencingDummyLeft::class, sprintf('%s|%1$s', CircularReferencingDummyLeft::class)];

        yield [CircularReferencingDummyRight::class, CircularReferencingDummyRight::class];
        yield [CircularReferencingDummyRight::class, sprintf('array<int, %s>', CircularReferencingDummyRight::class)];
        yield [CircularReferencingDummyRight::class, sprintf('array<string, %s>', CircularReferencingDummyRight::class)];
        yield [CircularReferencingDummyRight::class, sprintf('%s|%1$s', CircularReferencingDummyRight::class)];
    }
}
