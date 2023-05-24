<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Internal\Serialize\Csv;

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
    public function testSerialize(string $expected, mixed $data, string $type = null)
    {
        $this->assertSame($expected, $this->serializeAsString($data, (null !== $type) ? ['type' => $type] : []));
    }

    /**
     * @return iterable<array{0: string, 1: mixed, 2: string?}>
     */
    public static function serializeDataProvider(): iterable
    {
        yield ["0\n1\n", [1]];
        yield ["0\n\"foo bar\"\n", ['foo bar']];
        yield ["0\n\n", [null]];
        yield ["0\n0.01\n", [.01]];
        yield ["0\n\n", [false]];
        yield ["id,name\n1,dummy\n", [new ClassicDummy()]];
        yield ["id,name\n1,dummy\n", [new ClassicDummy()], 'array<int, object>'];
        yield ["0\n1\n", [DummyBackedEnum::ONE]];
        yield ["name\n\"\"\"quoted\"\" dummy\"\n", [new DummyWithQuotes()]];
        yield ["0\nfoo\n1\n", ['foo', true]];
        yield ["0\n1\n2\n3\n", [1, 2, 3], 'array'];
        yield ["0\n1\n2\n3\n", [1, 2, 3], 'iterable'];
        yield ["0\n1\n2\n3.12\n", [1, 2, 3.12], 'iterable<int, int|float>'];
        yield ["0\n1\n\n", [true, null], 'array<int, ?bool>'];
        yield ["a,c,e\nb,d,\n,d,f\n", [['a' => 'b', 'c' => 'd'], ['c' => 'd', 'e' => 'f']], 'array<int, array<string, string>>'];
        yield ["a,b\n,d\n", [['a' => false, 'b' => 'd']], 'array<int, array<string, string|bool>>'];
        yield ["0\n1\n", [true], 'mixed'];
    }

    public function testSerializeWithCsvOptions()
    {
        $this->assertSame("0,1\n\"1 2\",\n\"\\3\",4\n", $this->serializeAsString([['1 2'], ['\3', '4']]));
        $this->assertSame('0|1EOL_1 2_|EOL\\3|4EOL', $this->serializeAsString([['1 2'], ['\3', '4']], [
            'csv_end_of_line' => 'EOL',
            'csv_delimiter' => '|',
            'csv_enclosure' => '_',
            'csv_escape_char' => '',
        ]));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function serializeAsString(mixed $data, array $context = []): string
    {
        /** @var resource $resource */
        $resource = fopen('php://temp', 'w');
        serialize($data, $resource, 'csv', $context);

        rewind($resource);

        return stream_get_contents($resource);
    }
}
