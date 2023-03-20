<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Unmarshal\Json;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Exception\InvalidResourceException;
use Symfony\Component\Marshaller\Internal\TypeFactory;
use Symfony\Component\Marshaller\Internal\Unmarshal\Json\JsonDictSplitter;
use Symfony\Component\Marshaller\Internal\Unmarshal\LexerInterface;

class JsonDictSplitterTest extends TestCase
{
    public function testSplitNull()
    {
        $lexer = $this->createStub(LexerInterface::class);
        $lexer->method('tokens')->willReturn(new \ArrayIterator([['null', 0]]));

        $context = ['boundary' => [0, -1]];

        $this->assertNull((new JsonDictSplitter($lexer))->split(fopen('php://temp', 'r'), TypeFactory::createFromString('int'), $context));
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

        $boundaries = (new JsonDictSplitter($lexer))->split(fopen('php://temp', 'r'), TypeFactory::createFromString('int'), $context);

        $this->assertSame($expectedBoundaries, iterator_to_array($boundaries));
    }

    /**
     * @return iterable<array{0: list<array{0: int, 1: int}>, 1: list<array{0: string, 1: int}>}>
     */
    public function splitDataProvider(): iterable
    {
        yield [[], [['{', 0], ['}', 1]]];
        yield [['k' => [5, 2]], [['{', 0], ['"k"', 1], [':', 4], ['10', 5], ['}', 7]]];
        yield [['k' => [5, 4]], [['{', 0], ['"k"', 1], [':', 4], ['[', 5], ['10', 6], [']', 8], ['}', 9]]];
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

        iterator_to_array((new JsonDictSplitter($lexer))->split(fopen('php://temp', 'r'), TypeFactory::createFromString('int'), $context));
    }

    /**
     * @return iterable<array{0: list<array{0: string, 1: int}>}>
     */
    public function splitInvalidDataProvider(): iterable
    {
        yield [[['{', 0], ['100', 1]]];
        yield [[['{', 0], ['{', 1], ['}', 2]]];
        yield [[['{', 0], ['{', 1], ['}', 2], [']', 3]]];
    }
}
