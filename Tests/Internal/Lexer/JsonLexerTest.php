<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Lexer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Exception\InvalidResourceException;
use Symfony\Component\Marshaller\Internal\Lexer\JsonLexer;

final class JsonLexerTest extends TestCase
{
    /**
     * @dataProvider tokenizeDataProvider
     *
     * @param list<string> $expectedTokens
     */
    public function testTokenize(array $expectedTokens, string $content): void
    {
        /** @var resource $resource */
        $resource = fopen('php://memory', 'w+');

        fwrite($resource, $content);
        rewind($resource);

        $this->assertSame($expectedTokens, iterator_to_array((new JsonLexer())->tokens($resource, [])));
    }

    /**
     * @return iterable<array{0: list<string>, 1: string}>
     */
    public function tokenizeDataProvider(): iterable
    {
        yield [['1'], '1'];
        yield [['false'], 'false'];
        yield [['null'], 'null'];
        yield [['"string"'], '"string"'];

        yield [['[', ']'], '[]'];
        yield [['[', '1', ',', '[', '2', ']', ']'], '[1, [2]]'];

        yield [['{', '}'], '{}'];
        yield [['{', '"foo"', ':', '{', '"bar"', ':', '"baz"', '}', '}'], '{"foo": {"bar": "baz"}}'];
    }

    /**
     * @dataProvider validJsonDataProvider
     */
    public function testTokenizeValidJson(string $file): void
    {
        iterator_to_array((new JsonLexer())->tokens(fopen($file, 'r'), []));

        $this->addToAssertionCount(1);
    }

    /**
     * Pulled from https://github.com/nst/JSONTestSuite.
     *
     * @return iterable<array{0: string}>
     */
    public function validJsonDataProvider(): iterable
    {
        foreach (glob(__DIR__.'/../../Fixtures/Resources/json/valid/*') as $file) {
            yield [$file];
        }
    }

    /**
     * @dataProvider invalidJsonDataProvider
     */
    public function testCannotTokenizeInvalidJson(string $file): void
    {
        $this->expectException(InvalidResourceException::class);

        iterator_to_array((new JsonLexer())->tokens(fopen($file, 'r'), []));
    }

    /**
     * Pulled from https://github.com/nst/JSONTestSuite.
     *
     * @return iterable<array{0: string}>
     */
    public function invalidJsonDataProvider(): iterable
    {
        foreach (glob(__DIR__.'/../../Fixtures/Resources/json/invalid/*') as $file) {
            yield [$file];
        }
    }

    public function testTokenizeOverflowingBuffer(): void
    {
        /** @var resource $resource */
        $resource = fopen('php://memory', 'w+');

        $veryLongString = str_repeat('.', 8192);

        fwrite($resource, sprintf('"%s"', $veryLongString));
        rewind($resource);

        $this->assertSame([sprintf('"%s"', $veryLongString)], iterator_to_array((new JsonLexer())->tokens($resource, [])));
    }
}
