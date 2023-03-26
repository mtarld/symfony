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
use Symfony\Component\SerDes\Internal\Deserialize\Json\JsonListSplitter;
use Symfony\Component\SerDes\Internal\Deserialize\LexerInterface;
use Symfony\Component\SerDes\Internal\TypeFactory;

class JsonListSplitterTest extends TestCase
{
    public function testSplitNull()
    {
        $lexer = $this->createStub(LexerInterface::class);
        $lexer->method('tokens')->willReturn(new \ArrayIterator([['null', 0]]));

        $context = ['boundary' => [0, -1]];

        $this->assertNull((new JsonListSplitter($lexer))->split(fopen('php://temp', 'r'), TypeFactory::createFromString('int'), $context));
    }

    /**
     * @dataProvider splitDataProvider
     *
     * @param list<array{0: int, 1: int}>    $expectedBoundaries
     * @param list<array{0: string, 1: int}> $tokens
     */
    public function testSplit(array $expectedBoundaries, array $tokens)
    {
        $lexer = $this->createStub(LexerInterface::class);
        $lexer->method('tokens')->willReturn(new \ArrayIterator($tokens));

        $context = ['boundary' => [0, -1]];

        $boundaries = (new JsonListSplitter($lexer))->split(fopen('php://temp', 'r'), TypeFactory::createFromString('int'), $context);

        $this->assertSame($expectedBoundaries, iterator_to_array($boundaries));
    }

    /**
     * @return iterable<array{0: list<array{0: int, 1: int}>, 1: list<array{0: string, 1: int}>}>
     */
    public static function splitDataProvider(): iterable
    {
        yield [[], [['[', 0], [']', 1]]];
        yield [[[1, 3]], [['[', 0], ['100', 1], [']', 4]]];
        yield [[[1, 3], [5, 3]], [['[', 0], ['100', 1], [',', 4], ['200', 5], [']', 8]]];
        yield [[[1, 6]], [['[', 0], ['1', 1], ['[', 2], ['2', 3], [',', 4], ['3', 5], [']', 6], [']', 7]]];
        yield [[[1, 6]], [['[', 0], ['1', 1], ['{', 2], ['2', 3], [',', 4], ['3', 5], ['}', 6], [']', 7]]];
    }

    /**
     * @dataProvider splitInvalidDataProvider
     *
     * @param list<array{0: string, 1: int}> $tokens
     */
    public function testSplitInvalidThrowException(array $tokens)
    {
        $lexer = $this->createStub(LexerInterface::class);
        $lexer->method('tokens')->willReturn(new \ArrayIterator($tokens));

        $context = ['boundary' => [0, -1]];

        $this->expectException(InvalidResourceException::class);

        iterator_to_array((new JsonListSplitter($lexer))->split(fopen('php://temp', 'r'), TypeFactory::createFromString('int'), $context));
    }

    /**
     * @return iterable<array{0: list<array{0: string, 1: int}>}>
     */
    public static function splitInvalidDataProvider(): iterable
    {
        yield [[['[', 0], ['100', 1]]];
        yield [[['[', 0], ['[', 1], [']', 2]]];
        yield [[['[', 0], ['[', 1], [']', 2], ['}', 3]]];
    }
}
