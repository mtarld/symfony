<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Internal\Serialize\Json;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithQuotes;
use Symfony\Component\SerDes\Tests\Fixtures\Enum\DummyBackedEnum;

use function Symfony\Component\SerDes\serialize;

class SerializeTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sprintf('%s/symfony_ser_des', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    /**
     * @dataProvider serializeDataProvider
     */
    public function testSerialize(mixed $data, string $type = null)
    {
        $this->assertSame(json_encode($data), $this->serializeAsString($data, (null !== $type) ? ['type' => $type] : []));
    }

    /**
     * @return iterable<array{0: mixed, 1: string?}>
     */
    public static function serializeDataProvider(): iterable
    {
        yield [1];
        yield ['1'];
        yield ['foo'];
        yield [null];
        yield [.01];
        yield [false];
        yield [new ClassicDummy()];
        yield [new ClassicDummy(), 'object'];
        yield [DummyBackedEnum::ONE];
        yield [new DummyWithQuotes()];
        yield [['foo', true]];
        yield [[1, 2, 3], 'array<int, int>'];
        yield [[1, 2, 3.12], 'array<int, int|float>'];
        yield [[true, false, true], 'iterable<int, bool>'];
        yield [[false, null], 'array<int, ?bool>'];
        yield [['a' => 'b', 'c' => 'd'], 'array<string, string>'];
        yield [['a' => false, 'b' => 'd'], 'array<string, string|bool>'];
        yield [['"a"' => '"b"'], 'array<string, string>'];
        yield [['a' => 1, 'b' => null], 'iterable<string, ?string>'];
        yield [[1, 2.12, new ClassicDummy()], sprintf('array<int, int|float|%s>', ClassicDummy::class)];
        yield [true, 'mixed'];
    }

    public function testSerializeWithJsonEncodeFlags()
    {
        $this->assertSame('"123"', $this->serializeAsString('123'));
        $this->assertSame('123', $this->serializeAsString('123', ['json_encode_flags' => \JSON_NUMERIC_CHECK]));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function serializeAsString(mixed $data, array $context = []): string
    {
        /** @var resource $resource */
        $resource = fopen('php://temp', 'w');
        serialize($data, $resource, 'json', $context);

        rewind($resource);

        return stream_get_contents($resource);
    }
}
