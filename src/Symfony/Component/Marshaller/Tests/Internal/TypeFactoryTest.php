<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Exception\InvalidArgumentException;
use Symfony\Component\Marshaller\Exception\InvalidTypeException;
use Symfony\Component\Marshaller\Exception\UnsupportedTypeException;
use Symfony\Component\Marshaller\Internal\Type;
use Symfony\Component\Marshaller\Internal\TypeFactory;
use Symfony\Component\Marshaller\Internal\UnionType;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy;

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
    public function createFromStringDataProvider(): iterable
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

        // generic types
        yield [new Type('object', className: ClassicDummy::class, isGeneric: true, genericParameterTypes: [new Type('int')]), ClassicDummy::class.'<int>'];
        yield [new Type(
            'object',
            className: ClassicDummy::class,
            isGeneric: true,
            genericParameterTypes: [new Type('int', isGeneric: true, genericParameterTypes: [new Type('bool', isNullable: true)])],
        ), ClassicDummy::class.'<int<?bool>>'];

        // collection types
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

    public function testCreateThrowOnRawArray()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid generic parameter types of "array" type.');

        TypeFactory::createFromString('array');
    }
}
