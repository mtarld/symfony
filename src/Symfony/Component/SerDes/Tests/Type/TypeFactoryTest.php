<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Exception\InvalidTypeException;
use Symfony\Component\SerDes\Exception\UnsupportedTypeException;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\SerDes\Tests\Fixtures\Enum\DummyUnitEnum;
use Symfony\Component\SerDes\Type\Type;
use Symfony\Component\SerDes\Type\TypeFactory;
use Symfony\Component\SerDes\Type\UnionType;

class TypeFactoryTest extends TestCase
{
    /**
     * @dataProvider createFromStringDataProvider
     */
    public function testCreateFromString(Type|UnionType $expectedType, string $string)
    {
        $this->assertEquals($expectedType, TypeFactory::createFromString($string));
    }

    /**
     * @return iterable<array{0: Type|UnionType, 1: string}>
     */
    public static function createFromStringDataProvider(): iterable
    {
        // scalar types
        yield [new Type('null'), 'null'];
        yield [new Type('int'), 'int'];
        yield [new Type('string'), 'string'];
        yield [new Type('float'), 'float'];
        yield [new Type('bool'), 'bool'];
        yield [new Type('int', isNullable: true), '?int'];

        // object types
        yield [new Type('object', className: ClassicDummy::class), ClassicDummy::class];
        yield [new Type('object', isNullable: true, className: ClassicDummy::class), '?'.ClassicDummy::class];

        // enum types
        yield [new Type('enum', className: DummyBackedEnum::class), DummyBackedEnum::class];
        yield [new Type('enum', isNullable: true, className: DummyBackedEnum::class), '?'.DummyBackedEnum::class];

        // generic types
        yield [new Type('object', className: ClassicDummy::class, isGeneric: true, genericParameterTypes: [new Type('int')]), ClassicDummy::class.'<int>'];
        yield [new Type(
            'object',
            className: ClassicDummy::class,
            isGeneric: true,
            genericParameterTypes: [new Type('int', isGeneric: true, genericParameterTypes: [new Type('bool', isNullable: true)])],
        ), ClassicDummy::class.'<int<?bool>>'];

        // collection types
        yield [new Type('array'), 'array'];
        yield [new Type('array', isGeneric: true, genericParameterTypes: [new Type('int'), new Type('int')]), 'array<int, int>'];
        yield [new Type('array', isGeneric: true, genericParameterTypes: [new Type('int'), new Type('float')]), 'array<float>'];
        yield [
            new Type(
                'array',
                isGeneric: true,
                genericParameterTypes: [new Type('string'), new Type('array', isGeneric: true, genericParameterTypes: [new Type('int'), new Type('bool')])],
            ),
            'array<string, array<int, bool>>',
        ];
        yield [
            new Type(
                'array',
                isNullable: true,
                isGeneric: true,
                genericParameterTypes: [
                    new Type('string', isNullable: true),
                    new Type(
                        'array',
                        isNullable: true,
                        isGeneric: true,
                        genericParameterTypes: [new Type('int', isNullable: true), new Type('bool', isNullable: true)],
                    ),
                ]
            ),
            '?array<?string, ?array<?int, ?bool>>',
        ];
        yield [new Type('iterable', isGeneric: true, genericParameterTypes: [new Type('int'), new Type('int')]), 'iterable<int, int>'];
        yield [
            new Type(
                'iterable',
                isGeneric: true,
                genericParameterTypes: [new Type('string'), new Type('iterable', isGeneric: true, genericParameterTypes: [new Type('int'), new Type('bool')])],
            ),
            'iterable<string, iterable<bool>>',
        ];

        // union types
        yield [new UnionType([new Type('int'), new Type('string')]), 'int|string'];
        yield [new UnionType([new Type('int'), new Type('string'), new Type('null')]), 'int|string|null'];
        yield [new UnionType([new Type('int'), new Type('string'), new Type('null')]), 'int|?string|null'];
        yield [
            new UnionType([
                new Type(
                    'array',
                    isGeneric: true,
                    genericParameterTypes: [new Type('string'), new UnionType([
                        new Type('object', className: ClassicDummy::class, isGeneric: true, genericParameterTypes: [new Type('int')]),
                        new Type('float'),
                    ])],
                ),
                new Type('array', isGeneric: true, genericParameterTypes: [new Type('int'), new Type('bool')]),
            ]),
            sprintf('array<string, %s<int>|float>|array<int, bool>', ClassicDummy::class),
        ];
    }

    public function testCreateThrowOnIntersectionTypes()
    {
        $this->expectException(UnsupportedTypeException::class);

        TypeFactory::createFromString('foo&bar');
    }

    public function testCreateThrowOnInvalidGenericString()
    {
        $this->expectException(InvalidTypeException::class);

        TypeFactory::createFromString('array<int, array<string, bool>');
    }

    public function testCreateThrowOnUnitEnum()
    {
        $this->expectException(InvalidTypeException::class);

        TypeFactory::createFromString(DummyUnitEnum::class);
    }
}
