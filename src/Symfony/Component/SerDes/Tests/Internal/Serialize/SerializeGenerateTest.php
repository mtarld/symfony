<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Internal\Serialize;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Exception\CircularReferenceException;
use Symfony\Component\SerDes\Exception\UnsupportedFormatException;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\CircularReferencingDummyLeft;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\CircularReferencingDummyRight;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\SelfReferencingDummy;

use function Symfony\Component\SerDes\serialize_generate;

class SerializeGenerateTest extends TestCase
{
    /**
     * @dataProvider checkForCircularReferencesDataProvider
     */
    public function testCheckForCircularReferences(?string $expectedCircularClassName, string $type)
    {
        if (null !== $expectedCircularClassName) {
            $this->expectException(CircularReferenceException::class);
            $this->expectExceptionMessage(sprintf('A circular reference has been detected on class "%s".', $expectedCircularClassName));
        }

        serialize_generate($type, 'json');

        $this->addToAssertionCount(1);
    }

    /**
     * @return iterable<array{0: ?string, 1: string}>
     */
    public function checkForCircularReferencesDataProvider(): iterable
    {
        yield [null, ClassicDummy::class];
        yield [null, sprintf('array<int, %s>', ClassicDummy::class)];
        yield [null, sprintf('array<string, %s>', ClassicDummy::class)];
        yield [null, sprintf('%s|%1$s', ClassicDummy::class)];

        yield [SelfReferencingDummy::class, SelfReferencingDummy::class];
        yield [SelfReferencingDummy::class, sprintf('array<int, %s>', SelfReferencingDummy::class)];
        yield [SelfReferencingDummy::class, sprintf('array<string, %s>', SelfReferencingDummy::class)];
        yield [SelfReferencingDummy::class, sprintf('%s|%1$s', SelfReferencingDummy::class)];

        yield [CircularReferencingDummyLeft::class, CircularReferencingDummyLeft::class];
        yield [CircularReferencingDummyLeft::class, sprintf('array<int, %s>', CircularReferencingDummyLeft::class)];
        yield [CircularReferencingDummyLeft::class, sprintf('array<string, %s>', CircularReferencingDummyLeft::class)];
        yield [CircularReferencingDummyLeft::class, sprintf('%s|%1$s', CircularReferencingDummyLeft::class)];

        yield [CircularReferencingDummyRight::class, CircularReferencingDummyRight::class];
        yield [CircularReferencingDummyRight::class, sprintf('array<int, %s>', CircularReferencingDummyRight::class)];
        yield [CircularReferencingDummyRight::class, sprintf('array<string, %s>', CircularReferencingDummyRight::class)];
        yield [CircularReferencingDummyRight::class, sprintf('%s|%1$s', CircularReferencingDummyRight::class)];
    }

    public function testThrowOnUnknownFormat()
    {
        $this->expectException(UnsupportedFormatException::class);

        serialize_generate('int', 'unknown', []);
    }
}
