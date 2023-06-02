<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Internal\Deserialize\Json;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Exception\InvalidResourceException;
use Symfony\Component\SerDes\Exception\UnexpectedValueException;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;

use function Symfony\Component\SerDes\deserialize;

class DeserializeTest extends TestCase
{
    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testDeserialize(callable $deserialize)
    {
        $this->assertSame(json_decode('1', associative: true), $deserialize('1', 'int'));
        $this->assertSame(json_decode('"foo"', associative: true), $deserialize('"foo"', 'string'));
        $this->assertSame(json_decode('-1e100', associative: true), $deserialize('-1e100', 'float'));
        $this->assertSame(json_decode('true', associative: true), $deserialize('true', 'bool'));
        $this->assertSame(json_decode('null', associative: true), $deserialize('null', '?bool'));
        $this->assertSame(
            json_decode('[[1, 2], [3, null], null]', associative: true),
            $deserialize('[[1, 2], [3, null], null]', 'array<int, ?array<int, ?int>>'),
        );
        $this->assertSame(
            json_decode('{"foo": {"bar": 1, "baz": null}, "foo2": null}', associative: true),
            $deserialize('{"foo": {"bar": 1, "baz": null}, "foo2": null}', 'array<string, ?array<string, ?int>>'),
        );
        $this->assertSame(
            json_decode('{"foo": {"bar": 1, "baz": null}, "foo2": null}', associative: true),
            $deserialize('{"foo": {"bar": 1, "baz": null}, "foo2": null}', 'mixed'),
        );
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testDeserializeWithJsonDecodeFlags(callable $deserialize)
    {
        $this->assertSame('1.2345678901235E+29', $deserialize('123456789012345678901234567890', 'string'));
        $this->assertSame('123456789012345678901234567890', $deserialize('123456789012345678901234567890', 'string', ['json_decode_flags' => \JSON_BIGINT_AS_STRING]));
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testThrowOnInvalidList(callable $deserialize, bool $lazy)
    {
        $this->expectException($lazy ? InvalidResourceException::class : UnexpectedValueException::class);

        $deserialize('"foo"', 'array<int, int>');
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testThrowOnInvalidDict(callable $deserialize, bool $lazy)
    {
        $this->expectException($lazy ? InvalidResourceException::class : UnexpectedValueException::class);

        $deserialize('"foo"', 'array<string, int>');
    }

    /**
     * @dataProvider deserializeDataProvider
     *
     * @param callable(string, string, array<string, mixed>): mixed
     */
    public function testThrowOnInvalidObjectProperties(callable $deserialize, bool $lazy)
    {
        $this->expectException($lazy ? InvalidResourceException::class : UnexpectedValueException::class);

        $deserialize('"foo"', ClassicDummy::class);
    }

    /**
     * @return iterable<array{0: callable(mixed, string, array<string, mixed>): mixed, 1: bool}>
     */
    public static function deserializeDataProvider(): iterable
    {
        yield [fn (string $content, string $type, array $context = []): mixed => self::deserialize($content, $type, $context), false];
        yield [fn (string $content, string $type, array $context = []): mixed => self::deserialize($content, $type, ['lazy_reading' => true] + $context), true];
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

        return deserialize($resource, $type, 'json', $context);
    }
}
