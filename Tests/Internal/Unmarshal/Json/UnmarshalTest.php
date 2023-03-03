<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Unmarshal\Json;

use PHPUnit\Framework\TestCase;

use function Symfony\Component\Marshaller\unmarshal;

final class UnmarshalTest extends TestCase
{
    /**
     * @dataProvider unmarshalDataProvider
     */
    public function testUnmarshal(string $json, string $type): void
    {
        $expected = json_decode($json, associative: true);

        $this->assertSame($expected, $this->unmarshalString($json, $type, ['read_mode' => 'eager']));
        $this->assertSame($expected, $this->unmarshalString($json, $type, ['read_mode' => 'lazy']));
    }

    /**
     * @return iterable<array{0: string, 1: string}>
     */
    public function unmarshalDataProvider(): iterable
    {
        yield ['1', 'int'];
        yield ['"foo"', 'string'];
        yield ['-1e100', 'float'];
        yield ['true', 'bool'];
        yield ['null', '?bool'];
        yield ['[[1, 2], [3, null], null]', 'array<int, ?array<int, ?int>>'];
        yield ['{"foo": {"bar": 1, "baz": null}, "foo2": null}', 'array<string, ?array<string, ?int>>'];
    }

    public function testUnmarshalWithJsonDecodeFlags(): void
    {
        $this->assertSame('1.2345678901235E+29', $this->unmarshalString('123456789012345678901234567890', 'string'));
        $this->assertSame('123456789012345678901234567890', $this->unmarshalString('123456789012345678901234567890', 'string', ['json_decode_flags' => \JSON_BIGINT_AS_STRING]));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function unmarshalString(string $string, string $type, array $context = []): mixed
    {
        /** @var resource $resource */
        $resource = fopen('php://memory', 'w+');

        fwrite($resource, $string);
        rewind($resource);

        return unmarshal($resource, $type, 'json', $context);
    }
}
