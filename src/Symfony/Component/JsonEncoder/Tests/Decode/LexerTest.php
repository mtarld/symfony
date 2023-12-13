<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Tests\Decode;

use PHPUnit\Framework\TestCase;
use Symfony\Component\JsonEncoder\Decode\Lexer;
use Symfony\Component\JsonEncoder\Stream\BufferedStream;

class LexerTest extends TestCase
{
    public function testTokens()
    {
        $this->assertTokens([['1', 0]], '1');
        $this->assertTokens([['false', 0]], 'false');
        $this->assertTokens([['null', 0]], 'null');
        $this->assertTokens([['"string"', 0]], '"string"');
        $this->assertTokens([['[', 0], [']', 1]], '[]');
        $this->assertTokens([['[', 0], ['10', 2], [',', 4], ['20', 6], [']', 9]], '[ 10, 20 ]');
        $this->assertTokens([['[', 0], ['1', 1], [',', 2], ['[', 4], ['2', 5], [']', 6], [']', 8]], '[1, [2] ]');
        $this->assertTokens([['{', 0], ['}', 1]], '{}');
        $this->assertTokens([['{', 0], ['"foo"', 1], [':', 6], ['{', 8], ['"bar"', 9], [':', 14], ['"baz"', 15], ['}', 20], ['}', 21]], '{"foo": {"bar":"baz"}}');
    }

    public function testTokensSubset()
    {
        $this->assertTokens([['false', 7]], '[1, 2, false]', 7, 5);
    }

    public function testTokenizeOverflowingBuffer()
    {
        $veryLongString = sprintf('"%s"', str_repeat('.', 20000));

        $this->assertTokens([[$veryLongString, 0]], $veryLongString);
    }

    private function assertTokens(array $tokens, string $content, int $offset = 0, int $length = null): void
    {
        $resource = fopen('php://temp', 'w');
        fwrite($resource, $content);
        rewind($resource);

        $stream = new BufferedStream();
        $stream->write($content);
        $stream->rewind();

        $this->assertSame($tokens, iterator_to_array((new Lexer())->getTokens($resource, $offset, $length)));
    }
}
