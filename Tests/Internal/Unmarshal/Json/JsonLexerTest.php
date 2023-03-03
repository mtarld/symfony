<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Unmarshal\Json;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Exception\InvalidResourceException;
use Symfony\Component\Marshaller\Exception\RuntimeException;
use Symfony\Component\Marshaller\Internal\Unmarshal\Json\JsonLexer;

final class JsonLexerTest extends TestCase
{
    /**
     * @dataProvider tokensDataProvider
     *
     * @param list<string> $expectedTokens
     */
    public function testTokens(array $expectedTokens, string $content): void
    {
        $this->assertSame($expectedTokens, iterator_to_array((new JsonLexer())->tokens($this->createResource($content), 0, -1, [])));
    }

    /**
     * @return iterable<array{0: list<string>, 1: string}>
     */
    public function tokensDataProvider(): iterable
    {
        yield [[['1', 0]], '1'];
        yield [[['false', 0]], 'false'];
        yield [[['null', 0]], 'null'];
        yield [[['"string"', 0]], '"string"'];

        yield [[['[', 0], [']', 1]], '[]'];
        yield [[['[', 0], ['10', 2], [',', 4], ['20', 6], [']', 9]], '[ 10, 20 ]'];
        yield [[['[', 0], ['1', 1], [',', 2], ['[', 4], ['2', 5], [']', 6], [']', 8]], '[1, [2] ]'];

        yield [[['{', 0], ['}', 1]], '{}'];
        yield [[['{', 0], ['"foo"', 1], [':', 6], ['{', 8], ['"bar"', 9], [':', 14], ['"baz"', 15], ['}', 20], ['}', 21]], '{"foo": {"bar":"baz"}}'];

        yield [[['[', 3], ['1', 4], [']', 5]], "\xEF\xBB\xBF".'[1]'];
    }

    public function testTokensSubset(): void
    {
        $this->assertSame([['false', 7]], iterator_to_array((new JsonLexer())->tokens($this->createResource('[1, 2, false]'), 7, 5, [])));
    }

    public function testTokensThrowOnInvalidResource(): void
    {
        $this->expectException(RuntimeException::class);

        iterator_to_array((new JsonLexer())->tokens(fopen('foo', 'w'), 0, -1, []));
    }

    /**
     * @dataProvider validJsonTokensDataProvider
     */
    public function testValidJsonTokens(string $file): void
    {
        iterator_to_array((new JsonLexer())->tokens(fopen($file, 'r'), 0, -1, []));

        $this->addToAssertionCount(1);
    }

    /**
     * Pulled from https://github.com/nst/JSONTestSuite.
     *
     * @return iterable<array{0: string}>
     */
    public function validJsonTokensDataProvider(): iterable
    {
        foreach (glob(\dirname(__DIR__, 3).'/Fixtures/Resources/json/valid/*') as $file) {
            yield [$file];
        }
    }

    /**
     * @dataProvider invalidJsonTokensDataProvider
     */
    public function testInvalidJsonTokens(string $file): void
    {
        $this->markTestSkipped();
        $this->expectException(InvalidResourceException::class);

        iterator_to_array((new JsonLexer())->tokens(fopen($file, 'r'), []));
    }

    /**
     * Pulled from https://github.com/nst/JSONTestSuite.
     *
     * @return iterable<array{0: string}>
     */
    public function invalidJsonTokensDataProvider(): iterable
    {
        foreach (glob(\dirname(__DIR__, 3).'/Fixtures/Resources/json/invalid/*') as $file) {
            yield [$file];
        }
    }

    public function testTokenizeOverflowingBuffer(): void
    {
        $veryLongString = sprintf('"%s"', str_repeat('.', 20000));

        $this->assertSame([[$veryLongString, 0]], iterator_to_array((new JsonLexer())->tokens($this->createResource($veryLongString), 0, -1, [])));
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
