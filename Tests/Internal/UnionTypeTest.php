<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Type;
use Symfony\Component\Marshaller\Internal\UnionType;

final class UnionTypeTest extends TestCase
{
    public function testIsNullable(): void
    {
        $this->assertTrue((new UnionType([new Type('int'), new Type('null')]))->isNullable());
        $this->assertFalse((new UnionType([new Type('int'), new Type('string')]))->isNullable());
    }

    public function testAtLeastOneTypeIs(): void
    {
        $callable = fn (Type $t): bool => 'int' === $t->name();

        $this->assertTrue((new UnionType([new Type('int'), new Type('string')]))->atLeastOneTypeIs($callable));
        $this->assertFalse((new UnionType([new Type('float'), new Type('string')]))->atLeastOneTypeIs($callable));
    }

    /**
     * @dataProvider toStringDataProvider
     */
    public function testToString(string $expectedString, UnionType $type): void
    {
        $this->assertSame($expectedString, (string) $type);
    }

    /**
     * @return iterable<array{0: string, 1: UnionType}>
     */
    public function toStringDataProvider(): iterable
    {
        yield ['int|string', new UnionType([new Type('int'), new Type('string')])];
        yield ['int|string|null', new UnionType([new Type('int'), new Type('string'), new Type('null')])];
        yield [
            'array<string, string|float>|array<int, bool>',
            new UnionType([
                new Type(
                    'array',
                    isGeneric: true,
                    genericParameterTypes: [new Type('string'), new UnionType([new Type('string'), new Type('float')])],
                ),
                new Type('array', isGeneric: true, genericParameterTypes: [new Type('int'), new Type('bool')]),
            ]),
        ];
    }
}
