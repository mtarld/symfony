<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Internal\Deserialize\Csv;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\InvalidResourceException;
use Symfony\Component\Serializer\Internal\Deserialize\Csv\CsvDecoder;

class CsvDecoderTest extends TestCase
{
    public function testDecode()
    {
        $this->assertSame([['foo']], $this->decodeString('foo', []));
    }

    public function testDecodeWithUtf8Bom()
    {
        $this->assertSame([['foo', 'bar']], $this->decodeString('foo,bar', []));
        $this->assertSame([['foo,bar']], $this->decodeString('foo,bar', ['csv_separator' => '|']));
    }

    public function testDecodeWithCsvOptions()
    {
        $this->assertSame([['foo', 'bar']], $this->decodeString('foo,bar', []));
        $this->assertSame([['foo', 'bar']], $this->decodeString("\xEF\xBB\xBFfoo,bar", []));
    }

    public function testDecodeThrowOnInvalidResource()
    {
        $this->expectException(InvalidResourceException::class);

        iterator_to_array((new CsvDecoder())->decode(fopen(sprintf('%s/%s', sys_get_temp_dir(), uniqid()), 'w'), []));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function decodeString(string $string, array $context): mixed
    {
        /** @var resource $resource */
        $resource = fopen('php://memory', 'w+');

        fwrite($resource, $string);
        rewind($resource);

        return iterator_to_array((new CsvDecoder())->decode($resource, $context));
    }
}
