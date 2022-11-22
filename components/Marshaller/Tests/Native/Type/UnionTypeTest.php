<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Type\Type;
use Symfony\Component\Marshaller\Native\Type\UnionType;

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

    public function testEveryTypeIs(): void
    {
        $callable = fn (Type $t): bool => $t->isScalar();

        $this->assertTrue((new UnionType([new Type('int'), new Type('string')]))->everyTypeIs($callable));
        $this->assertFalse((new UnionType([new Type('int'), new Type('array')]))->everyTypeIs($callable));
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
                    collectionKeyType: new Type('string'),
                    collectionValueType: new UnionType([new Type('string'), new Type('float')]),
                ),
                new Type('array', collectionKeyType: new Type('int'), collectionValueType: new Type('bool')),
            ]),
        ];
    }
}
