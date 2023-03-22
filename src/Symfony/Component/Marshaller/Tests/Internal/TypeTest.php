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
use Symfony\Component\Marshaller\Exception\LogicException;
use Symfony\Component\Marshaller\Internal\Type;
use Symfony\Component\Marshaller\Internal\UnionType;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy;

class TypeTest extends TestCase
{
    /**
     * @dataProvider toStringDataProvider
     */
    public function testToString(string $expectedString, Type|UnionType $type)
    {
        $this->assertSame($expectedString, (string) $type);
    }

    /**
     * @return iterable<array{0: string, 1: Type|UnionType}>
     */
    public function toStringDataProvider(): iterable
    {
        // scalar types
        yield ['null', new Type('null')];
        yield ['int', new Type('int')];
        yield ['string', new Type('string')];
        yield ['float', new Type('float')];
        yield ['bool', new Type('bool')];
        yield ['?int', new Type('int', isNullable: true)];

        // object types
        yield [ClassicDummy::class, new Type('object', className: ClassicDummy::class)];
        yield ['?'.ClassicDummy::class, new Type('object', isNullable: true, className: ClassicDummy::class)];

        // generic types
        yield [ClassicDummy::class.'<int>', new Type('object', className: ClassicDummy::class, isGeneric: true, genericParameterTypes: [new Type('int')])];
        yield [
            ClassicDummy::class.'<int<?bool>>',
            new Type(
                'object',
                className: ClassicDummy::class,
                isGeneric: true,
                genericParameterTypes: [new Type('int', isGeneric: true, genericParameterTypes: [new Type('bool', isNullable: true)])],
            ),
        ];

        // collection types
        yield ['array<int, int>', new Type('array', isGeneric: true, genericParameterTypes: [new Type('int'), new Type('int')])];
        yield ['array<int, float>', new Type('array', isGeneric: true, genericParameterTypes: [new Type('int'), new Type('float')])];
        yield [
            'array<string, array<int, bool>>',
            new Type(
                'array',
                isGeneric: true,
                genericParameterTypes: [
                    new Type('string'),
                    new Type('array', isGeneric: true, genericParameterTypes: [new Type('int'), new Type('bool')]),
                ],
            ),
        ];
        yield [
            '?array<?string, ?array<?int, ?bool>>',
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
                        genericParameterTypes: [
                            new Type('int', isNullable: true),
                            new Type('bool', isNullable: true),
                        ],
                    ),
                ],
            ),
        ];
    }

    public function testCannotCreateObjectWithoutClassName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing className of "object" type.');

        new Type('object');
    }

    public function testCannotCreateWithTypeAndWithoutValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid generic parameter types of "array" type.');

        new Type('array', isGeneric: true, genericParameterTypes: [new Type('int')]);
    }

    public function testCannotCreateGenericWithoutGenericTypes()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing generic parameter types of "object" type.');

        new Type('object', className: ClassicDummy::class, isGeneric: true, genericParameterTypes: []);
    }

    public function testCannotGetClassNameOnNonObject()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot get class on "int" type as it\'s not an object.');

        (new Type('int'))->className();
    }

    public function testCannotGetCollectionKeyTypeOnNonCollection()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot get collection key type on "int" type as it\'s not a collection.');

        (new Type('int'))->collectionKeyType();
    }

    public function testCannotGetCollectionValueTypeOnNonCollection()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot get collection value type on "int" type as it\'s not a collection.');

        (new Type('int'))->collectionValueType();
    }

    /**
     * @dataProvider isserDataProvider
     */
    public function testIsser(Type $type, bool $scalar, bool $nullable, bool $object, bool $collection, bool $list, bool $dict, bool $generic)
    {
        $this->assertSame($scalar, $type->isScalar());
        $this->assertSame($nullable, $type->isNull());
        $this->assertSame($object, $type->isObject());
        $this->assertSame($collection, $type->isCollection());
        $this->assertSame($list, $type->isList());
        $this->assertSame($dict, $type->isDict());
        $this->assertSame($generic, $type->isGeneric());
    }

    /**
     * @return iterable<array{type: Type, scalar: bool, null: bool, object: bool, collection: bool, list: bool, dict: bool}>
     */
    public function isserDataProvider(): iterable
    {
        yield ['type' => new Type('int'), 'scalar' => true, 'null' => false, 'object' => false, 'collection' => false, 'list' => false, 'dict' => false, 'generic' => false];
        yield ['type' => new Type('string'), 'scalar' => true, 'null' => false, 'object' => false, 'collection' => false, 'list' => false, 'dict' => false, 'generic' => false];
        yield ['type' => new Type('bool'), 'scalar' => true, 'null' => false, 'object' => false, 'collection' => false, 'list' => false, 'dict' => false, 'generic' => false];
        yield ['type' => new Type('float'), 'scalar' => true, 'null' => false, 'object' => false, 'collection' => false, 'list' => false, 'dict' => false, 'generic' => false];
        yield ['type' => new Type('null'), 'scalar' => false, 'null' => true, 'object' => false, 'collection' => false, 'list' => false, 'dict' => false, 'generic' => false];
        yield [
            'type' => new Type('object', className: ClassicDummy::class),
            'scalar' => false,
            'null' => false,
            'object' => true,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
        ];
        yield [
            'type' => new Type('object', className: ClassicDummy::class, isGeneric: true, genericParameterTypes: [new Type('int')]),
            'scalar' => false,
            'null' => false,
            'object' => true,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => true,
        ];
        yield [
            'type' => new Type('array', isGeneric: true, genericParameterTypes: [new Type('int'), new Type('int')]),
            'scalar' => false,
            'null' => false,
            'object' => false,
            'collection' => true,
            'list' => true,
            'dict' => false,
            'generic' => true,
        ];
        yield [
            'type' => new Type('array', isGeneric: true, genericParameterTypes: [new Type('string'), new Type('int')]),
            'scalar' => false,
            'null' => false,
            'object' => false,
            'collection' => true,
            'list' => false,
            'dict' => true,
            'generic' => true,
        ];
    }
}
