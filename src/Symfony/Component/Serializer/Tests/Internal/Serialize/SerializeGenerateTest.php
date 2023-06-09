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
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\CircularReferencingDummyLeft;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\CircularReferencingDummyRight;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\SelfReferencingDummy;
use Symfony\Component\Serializer\Type\TypeFactory;

use function Symfony\Component\Serializer\serialize_generate;

class SerializeGenerateTest extends TestCase
{
    /**
     * @dataProvider checkForCircularReferencesDataProvider
     */
    public function testCheckForCircularReferences(bool $expectCircularReference, string $type)
    {
        if ($expectCircularReference) {
            $this->expectException(CircularReferenceException::class);
        }

        serialize_generate(TypeFactory::createFromString($type), 'json');

        $this->addToAssertionCount(1);
    }

    /**
     * @return iterable<array{0: bool, 1: string}>
     */
    public static function checkForCircularReferencesDataProvider(): iterable
    {
        yield [false, ClassicDummy::class];
        yield [false, sprintf('array<int, %s>', ClassicDummy::class)];
        yield [false, sprintf('array<string, %s>', ClassicDummy::class)];
        yield [false, sprintf('%s|%1$s', ClassicDummy::class)];

        yield [true, SelfReferencingDummy::class];
        yield [true, sprintf('array<int, %s>', SelfReferencingDummy::class)];
        yield [true, sprintf('array<string, %s>', SelfReferencingDummy::class)];
        yield [true, sprintf('%s|%1$s', SelfReferencingDummy::class)];

        yield [true, CircularReferencingDummyLeft::class];
        yield [true, sprintf('array<int, %s>', CircularReferencingDummyLeft::class)];
        yield [true, sprintf('array<string, %s>', CircularReferencingDummyLeft::class)];
        yield [true, sprintf('%s|%1$s', CircularReferencingDummyLeft::class)];

        yield [true, CircularReferencingDummyRight::class];
        yield [true, sprintf('array<int, %s>', CircularReferencingDummyRight::class)];
        yield [true, sprintf('array<string, %s>', CircularReferencingDummyRight::class)];
        yield [true, sprintf('%s|%1$s', CircularReferencingDummyRight::class)];
    }

    public function testThrowOnUnknownFormat()
    {
        $this->expectException(UnsupportedException::class);

        serialize_generate(TypeFactory::createFromString('int'), 'unknown', []);
    }
}
