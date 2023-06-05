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
use Symfony\Component\SerDes\Internal\Deserialize\Json\JsonDictSplitter;
use Symfony\Component\SerDes\Internal\TypeFactory;

class JsonDictSplitterTest extends TestCase
{
    public function testSplitNull()
    {
        $this->assertNull((new JsonDictSplitter())->split(
            $this->createResource('null'),
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
        $this->assertSame($expectedBoundaries, iterator_to_array((new JsonDictSplitter())->split(
            self::createResource($content),
            TypeFactory::createFromString('useless'),
            ['boundary' => [0, -1]],
        )));
    }

    /**
     * @return iterable<array{0: list<array{0: int, 1: int}>, 1: list<array{0: string, 1: int}>}>
     */
    public static function splitDataProvider(): iterable
    {
        yield [[], '{}'];
        yield [['k' => [5, 2]], '{"k":10}'];
        yield [['k' => [5, 4]], '{"k":[10]}'];
    }

    /**
     * @dataProvider splitInvalidDataProvider
     */
    public function testSplitInvalidThrowException(string $content)
    {
        $this->expectException(InvalidResourceException::class);

        iterator_to_array((new JsonDictSplitter())->split(
            self::createResource($content),
            TypeFactory::createFromString('useless'),
            ['boundary' => [0, -1]],
        ));
    }

    /**
     * @return iterable<array{0: list<array{0: string, 1: int}>}>
     */
    public static function splitInvalidDataProvider(): iterable
    {
        yield ['{100'];
        yield ['{{}'];
        yield ['{{}]'];
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
