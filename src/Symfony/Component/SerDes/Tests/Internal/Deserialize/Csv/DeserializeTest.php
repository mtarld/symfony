<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Internal\Deserialize\Csv;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Exception\InvalidArgumentException;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;

use function Symfony\Component\SerDes\deserialize;

class DeserializeTest extends TestCase
{
    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testDeserializeScalar(callable $deserialize)
    {
        $this->assertSame([1, 2, 3], $deserialize("0\n1\n2\n3\n", 'array<int, int>'));
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testDeserializeMixed(callable $deserialize)
    {
        $this->assertSame(['1', '2', '3'], $deserialize("0\n1\n2\n3\n", 'array<int, mixed>'));
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testDeserializeList(callable $deserialize)
    {
        $this->assertSame([[1], [2, 3]], $deserialize("0,1\n1\n2,3\n", 'array<int, array<int, int>>'));
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testDeserializeDict(callable $deserialize)
    {
        $this->assertSame([['foo' => 1, 'bar' => null], ['foo' => null, 'bar' => 2]], $deserialize("foo,bar\n1,\n,2\n", 'array<int, array<string, ?int>>'));
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testDeserializeObject(callable $deserialize)
    {
        $dummyOne = new ClassicDummy();
        $dummyOne->id = 1;

        $dummyTwo = new ClassicDummy();
        $dummyTwo->name = 'two';

        $this->assertEquals([$dummyOne, $dummyTwo], $deserialize("id,name\n1,\n,two\n", sprintf('array<int, %s>', ClassicDummy::class)));
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testDeserializeWithCsvOptions(callable $deserialize)
    {
        $this->assertSame([['foo', 'bar']], $deserialize("0,1\nfoo,bar", 'array<int, array<int, string>>'));
        $this->assertSame([['foo,bar']], $deserialize("0,1\nfoo,bar", 'array<int, array<int, string>>', ['csv_separator' => '|']));
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testThrowOnNotAFirstLevelList(callable $deserialize)
    {
        $this->expectException(InvalidArgumentException::class);

        $deserialize("0\nuseless", 'int');
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testThrowOnListTooDeep(callable $deserialize)
    {
        $this->expectException(InvalidArgumentException::class);

        $deserialize("0\nuseless", 'array<int, array<int, array<int, string>>>');
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testThrowOnDictTooDeep(callable $deserialize)
    {
        $this->expectException(InvalidArgumentException::class);

        $deserialize("0\nuseless", 'array<int, array<string, array<string, string>>>');
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testThrowOnObjectTooDeep(callable $deserialize)
    {
        $this->expectException(InvalidArgumentException::class);

        $foo = new class() {};

        dd($deserialize("0\nuseless", sprintf('array<int, array<string, %s>>', $foo::class)));
    }

    /**
     * @return iterable<array{0: callable(mixed, string, array<string, mixed>): mixed, 1: bool}>
     */
    public static function deserializeDataProvider(): iterable
    {
        yield [
            fn (string $content, string $type, array $context = []): mixed => self::deserialize($content, $type, $context),
            false,
        ];

        yield [
            fn (string $content, string $type, array $context = []): mixed => iterator_to_array(self::deserialize($content, $type, ['lazy_reading' => true] + $context)),
            true,
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function deserialize(string $content, string $type, array $context): mixed
    {
        /** @var resource $resource */
        $resource = fopen('php://memory', 'w+');

        fwrite($resource, $content);
        rewind($resource);

        return deserialize($resource, $type, 'csv', $context);
    }
}
