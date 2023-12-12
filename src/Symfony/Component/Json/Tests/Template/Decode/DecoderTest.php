<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json\Tests\Template\Decode;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Json\Template\Decode\Decoder;
use Symfony\Component\JsonEncoder\Exception\UnexpectedValueException;
use Symfony\Component\JsonEncoder\Stream\BufferedStream;

class DecoderTest extends TestCase
{
    public function testDecode()
    {
        $this->assertDecoded('foo', '"foo"');
    }

    public function testDecodeSubset()
    {
        $this->assertDecoded('bar', '["foo","bar","baz"]', 7, 5);
    }

    public function testDecodeWithJsonDecodeFlags()
    {
        $this->assertDecoded(1.2345678901234568E+29, '123456789012345678901234567890');
        $this->assertDecoded('123456789012345678901234567890', '123456789012345678901234567890', flags: \JSON_BIGINT_AS_STRING);
    }

    public function testDecodeThrowOnInvalidJsonString()
    {
        $this->expectException(UnexpectedValueException::class);

        Decoder::decodeString('foo"');
    }

    public function testDecodeThrowOnInvalidJsonStream()
    {
        $this->expectException(UnexpectedValueException::class);

        $stream = new BufferedStream();
        $stream->write('foo"');
        $stream->rewind();

        Decoder::decodeStream($stream);
    }

    private function assertDecoded(mixed $decoded, string $encoded, int $offset = 0, int $length = null, int $flags = 0): void
    {
        if (0 === $offset && null === $length) {
            $this->assertEquals($decoded, Decoder::decodeString($encoded, $flags));
        }

        $stream = new BufferedStream();
        $stream->write($encoded);
        $stream->rewind();

        $this->assertEquals($decoded, Decoder::decodeStream($stream, $offset, $length, $flags));

        $resource = fopen('php://temp', 'w');
        fwrite($resource, $encoded);
        rewind($resource);

        $this->assertEquals($decoded, Decoder::decodeStream($resource, $offset, $length, $flags));
    }
}
