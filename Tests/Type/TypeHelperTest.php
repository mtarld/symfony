<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithMethods;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithQuotes;
use Symfony\Component\Marshaller\Type\TypeHelper;

final class TypeHelperTest extends TestCase
{
    /**
     * @dataProvider extractClassNamesDataProvider
     *
     * @param list<string> $expectedClassNames
     */
    public function testExtractClassNames(array $expectedClassNames, string $type): void
    {
        $this->assertSame($expectedClassNames, (new TypeHelper())->extractClassNames($type));
    }

    /**
     * @return iterable<array{0: list<string>, 1: string}>
     */
    public function extractClassNamesDataProvider(): iterable
    {
        yield [[], 'int'];
        yield [[ClassicDummy::class], ClassicDummy::class];
        yield [[\Stringable::class], \Stringable::class];

        yield [[ClassicDummy::class], sprintf('array<int, %s>', ClassicDummy::class)];
        yield [[ClassicDummy::class], sprintf('array<%s>', ClassicDummy::class)];
        yield [[ClassicDummy::class], sprintf('array<%s, %1$s>', ClassicDummy::class)];
        yield [[ClassicDummy::class, DummyWithMethods::class], sprintf('array<%s, %s>', ClassicDummy::class, DummyWithMethods::class)];

        yield [[ClassicDummy::class], sprintf('int|%s', ClassicDummy::class)];
        yield [[ClassicDummy::class], sprintf('int|%s|%1$s', ClassicDummy::class)];
        yield [[ClassicDummy::class, DummyWithMethods::class], sprintf('int|%s|%s', ClassicDummy::class, DummyWithMethods::class)];

        yield [[ClassicDummy::class], sprintf('array<int, %s>|%1$s|list<%1$s>', ClassicDummy::class)];
        yield [[ClassicDummy::class, DummyWithMethods::class, DummyWithQuotes::class], sprintf('array<int, %s>|%s|list<%s>', ClassicDummy::class, DummyWithMethods::class, DummyWithQuotes::class)];
    }
}
