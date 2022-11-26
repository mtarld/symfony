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
    public function testGenerateTemplate(): void
    {
        $lines = explode("\n", marshal_generate('int', 'json'));
        array_pop($lines);

        $this->assertSame([
            '<?php',
            '',
            '/**',
            ' * @param int $data',
            ' * @param resource $resource',
            ' */',
            'return static function (mixed $data, $resource, array $context): void {',
            '    \fwrite($resource, $data);',
            '};',
        ], $lines);
    }

    public function testGenerateTemplateWithCustomAccessor(): void
    {
        $lines = explode("\n", marshal_generate('int', 'json', ['accessor' => '$foo']));
        array_pop($lines);

        $this->assertSame([
            '<?php',
            '',
            '/**',
            ' * @param int $foo',
            ' * @param resource $resource',
            ' */',
            'return static function (mixed $foo, $resource, array $context): void {',
            '    \fwrite($resource, $foo);',
            '};',
        ], $lines);
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
