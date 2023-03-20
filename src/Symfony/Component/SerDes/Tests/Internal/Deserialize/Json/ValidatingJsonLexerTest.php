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
use Symfony\Component\SerDes\Internal\Deserialize\Json\JsonLexer;
use Symfony\Component\SerDes\Internal\Deserialize\Json\ValidatingJsonLexer;

class ValidatingJsonLexerTest extends TestCase
{
    public function testTokens()
    {
        /** @var resource $resource */
        $resource = fopen('php://temp', 'w');

        fwrite($resource, '1');
        rewind($resource);

        $this->assertSame([['1', 0]], iterator_to_array((new ValidatingJsonLexer(new JsonLexer()))->tokens($resource, 0, -1, [])));
    }

    public function testThrowOnInvalidJsonTokens(string $file)
    {
        $this->expectException(InvalidResourceException::class);

        $resource = fopen('php://temp');
        fwrite('{"foo}');
        rewind($resource);

        iterator_to_array((new ValidatingJsonLexer(new JsonLexer()))->tokens($resource, 0, -1, []));
    }
}
