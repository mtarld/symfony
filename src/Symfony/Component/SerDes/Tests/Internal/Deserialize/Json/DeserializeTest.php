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

use function Symfony\Component\SerDes\deserialize;

class DeserializeTest extends TestCase
{
    /**
     * @dataProvider deserializeDataProvider
     */
    public function testDeserialize(string $json, string $type)
    {
        $expected = json_decode($json, associative: true);

        $this->assertSame($expected, $this->deserializeString($json, $type, ['lazy_reading' => true]));
        $this->assertSame($expected, $this->deserializeString($json, $type, ['lazy_reading' => false]));
    }

    /**
     * @return iterable<array{0: string, 1: string}>
     */
    public static function deserializeDataProvider(): iterable
    {
        yield ['1', 'int'];
        yield ['"foo"', 'string'];
        yield ['-1e100', 'float'];
        yield ['true', 'bool'];
        yield ['null', '?bool'];
        yield ['[[1, 2], [3, null], null]', 'array<int, ?array<int, ?int>>'];
        yield ['{"foo": {"bar": 1, "baz": null}, "foo2": null}', 'array<string, ?array<string, ?int>>'];
    }

    public function testDeserializeWithJsonDecodeFlags()
    {
        $this->assertSame('1.2345678901235E+29', $this->deserializeString('123456789012345678901234567890', 'string'));
        $this->assertSame('123456789012345678901234567890', $this->deserializeString('123456789012345678901234567890', 'string', ['json_decode_flags' => \JSON_BIGINT_AS_STRING]));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function deserializeString(string $string, string $type, array $context = []): mixed
    {
        /** @var resource $resource */
        $resource = fopen('php://memory', 'w+');

        fwrite($resource, $string);
        rewind($resource);

        return deserialize($resource, $type, 'json', $context);
    }
}
