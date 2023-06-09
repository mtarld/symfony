<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Internal\Serialize;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\Serializer\Type\TypeFactory;
use Symfony\Component\Serializer\Type\TypeSorter;

class TypeSorterTest extends TestCase
{
    /**
     * @dataProvider sortByPrecisionDataProvider
     *
     * @param list<string> $expectedSortedTypes
     * @param list<string> $types
     */
    public function testSortByPrecision(array $expectedSortedTypes, array $types)
    {
        $types = array_map(fn (string $t): Type => TypeFactory::createFromString($t), $types);
        $sortedTypes = array_map(fn (Type $t): string => (string) $t, (new TypeSorter())->sortByPrecision($types));

        $this->assertEquals($expectedSortedTypes, $sortedTypes);
    }

    /**
     * @return iterable<array{0: list<string>, 1: list<string>}
     */
    public static function sortByPrecisionDataProvider(): iterable
    {
        yield [['int', 'string'], ['int', 'string']];
        yield [['int'], ['int', 'int']];
        yield [['int', Leaf::class], [Leaf::class, 'int']];

        yield [[Leaf::class], [Leaf::class, Leaf::class]];
        yield [[Leaf::class, Branch::class, Root::class], [Branch::class, Root::class, Leaf::class]];
        yield [[Branch::class, Root::class], [Root::class, Branch::class]];
        yield [['int', Leaf::class, Root::class], [Leaf::class, Root::class, 'int']];
    }

    /**
     * @dataProvider throwIfSameHierarchicalLevelDataProvider
     *
     * @param list<Type> $types
     */
    public function testThrowIfSameHierarchicalLevel(bool $expectException, array $types)
    {
        if ($expectException) {
            $this->expectException(LogicException::class);
        }

        (new TypeSorter())->sortByPrecision($types);

        $this->addToAssertionCount(1);
    }

    /**
     * @return iterable<array{0: bool, 1: list<Type>}
     */
    public static function throwIfSameHierarchicalLevelDataProvider(): iterable
    {
        yield [false, [new Type('object', className: Leaf::class), new Type('int')]];
        yield [false, [new Type('object', className: Branch::class), new Type('object', className: Root::class), new Type('object', className: Leaf::class)]];
        yield [false, [new Type('object', className: Leaf::class), new Type('object', className: Root::class)]];
        yield [true, [new Type('object', className: Leaf::class), new Type('object', className: Leaf2::class)]];
        yield [true, [new Type('object', className: Leaf::class), new Type('object', className: Branch2::class)]];
    }
}

abstract class Root
{
}

abstract class Branch extends Root
{
}

abstract class Branch2 extends Root
{
}

class Leaf extends Branch
{
}

class Leaf2 extends Branch2
{
}
