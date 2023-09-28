<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Tests\Unmarshal\Template;

use PHPUnit\Framework\TestCase;
use Symfony\Component\JsonMarshaller\Exception\InvalidResourceException;
use Symfony\Component\JsonMarshaller\Unmarshal\Template\Decoder;

class DecoderTest extends TestCase
{
    public function testDecode()
    {
        $this->assertSame('foo', Decoder::decode($this->createResource('"foo"'), 0, -1, []));
    }

    public function testDecodeSubset()
    {
        $this->assertSame('bar', Decoder::decode($this->createResource('["foo","bar","baz"]'), 7, 5, []));
    }

    public function testDecodeWithJsonDecodeFlags()
    {
        $this->assertSame(
            1.2345678901234568E+29,
            Decoder::decode($this->createResource('123456789012345678901234567890'), 0, -1, []),
        );

        $this->assertSame(
            '123456789012345678901234567890',
            Decoder::decode($this->createResource('123456789012345678901234567890'), 0, -1, ['json_decode_flags' => \JSON_BIGINT_AS_STRING]),
        );
    }

    public function testDecodeThrowOnInvalidResource()
    {
        $this->expectException(InvalidResourceException::class);

        Decoder::decode(fopen(sprintf('%s/%s', sys_get_temp_dir(), uniqid()), 'w'), 0, -1, []);
    }

    public function testDecodeThrowOnInvalidJson()
    {
        $this->expectException(InvalidResourceException::class);

        Decoder::decode($this->createResource('foo"'), 0, -1, []);
    }

    /**
     * @return resource
     */
    private function createResource(string $content): mixed
    {
        /** @var resource $resource */
        $resource = fopen('php://temp', 'w');

        fwrite($resource, $content);
        rewind($resource);

        return $resource;
    }
}
