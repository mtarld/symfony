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
use Symfony\Component\SerDes\Type\TypeFactory;

class JsonListSplitterTest extends TestCase
{
    public function testSplitNull()
    {
        $this->assertNull((new JsonListSplitter())->split(
            self::createResource('null'),
            TypeFactory::createFromString('useless'),
            ['boundary' => [0, -1]],
        ));
    }

    /**
     * @dataProvider splitDataProvider
     *
     * @param list<array{0: int, 1: int}> $expectedBoundaries
     */
    public function testSplit(array $expectedBoundaries, string $content)
    {
        $this->assertSame($expectedBoundaries, iterator_to_array((new JsonListSplitter())->split(
            self::createResource($content),
            TypeFactory::createFromString('useless'),
            ['boundary' => [0, -1]],
        )));
    }

    /**
     * @return iterable<array{0: list<array{0: int, 1: int}>, 1: string}>
     */
    public static function splitDataProvider(): iterable
    {
        yield [[], '[]'];
        yield [[[1, 3]], '[100]'];
        yield [[[1, 3], [5, 3]], '[100,200]'];
        yield [[[1, 1], [3, 5]], '[1,[2,3]]'];
        yield [[[1, 1], [3, 5]], '[1,{2:3}]'];
    }

    /**
     * @dataProvider splitInvalidDataProvider
     */
    public function testSplitInvalidThrowException(string $content)
    {
        $this->expectException(InvalidResourceException::class);

        iterator_to_array((new JsonListSplitter())->split(
            self::createResource($content),
            TypeFactory::createFromString('useless'),
            ['boundary' => [0, -1]],
        ));
    }

    /**
     * @return iterable<array{0: string}>
     */
    public static function splitInvalidDataProvider(): iterable
    {
        yield ['[100'];
        yield ['[[]'];
        yield ['[[]}'];
    }

    /**
     * @return resource
     */
    private static function createResource(string $content): mixed
    {
        /** @var resource $resource */
        $resource = fopen('php://memory', 'w+');

        fwrite($resource, $content);
        rewind($resource);

        return $resource;
    }
}
