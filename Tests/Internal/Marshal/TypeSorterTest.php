<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Marshal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Exception\LogicException;
use Symfony\Component\Marshaller\Internal\Marshal\TypeSorter;
use Symfony\Component\Marshaller\Internal\Type;
use Symfony\Component\Marshaller\Internal\TypeFactory;

final class TypeSorterTest extends TestCase
{
    /**
     * @dataProvider sortByPrecisionDataProvider
     *
     * @param list<string> $expectedSortedTypes
     * @param list<string> $types
     */
    public function testSortByPrecision(array $expectedSortedTypes, array $types): void
    {
        $types = array_map(fn (string $t): Type => TypeFactory::createFromString($t), $types);
        $sortedTypes = array_map(fn (Type $t): string => (string) $t, (new TypeSorter())->sortByPrecision($types));

        $this->assertEquals($expectedSortedTypes, $sortedTypes);
    }

    /**
     * @return iterable<array{0: list<string>, 1: list<string>}
     */
    public function sortByPrecisionDataProvider(): iterable
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
    public function testThrowIfSameHierarchicalLevel(bool $expectException, array $types): void
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
    public function throwIfSameHierarchicalLevelDataProvider(): iterable
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

final class Leaf extends Branch
{
}

final class Leaf2 extends Branch2
{
}
