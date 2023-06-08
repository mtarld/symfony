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
use Symfony\Component\SerDes\Exception\InvalidArgumentException;
use Symfony\Component\SerDes\Exception\LogicException;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\SerDes\Type\Type;

class TypeTest extends TestCase
{
    /**
     * @dataProvider toStringDataProvider
     */
    public function testToString(string $expectedString, Type $type)
    {
        $this->assertSame($expectedString, (string) $type);
    }

    /**
     * @return iterable<array{0: string, 1: Type}>
     */
    public static function toStringDataProvider(): iterable
    {
        // scalar types
        yield ['null', new Type('null')];
        yield ['int', new Type('int')];
        yield ['string', new Type('string')];
        yield ['float', new Type('float')];
        yield ['bool', new Type('bool')];
        yield ['?int', new Type('int', isNullable: true)];

        // object types
        yield ['object', new Type('object')];
        yield [ClassicDummy::class, new Type('object', className: ClassicDummy::class)];
        yield ['?'.ClassicDummy::class, new Type('object', isNullable: true, className: ClassicDummy::class)];

        // enum types
        yield [DummyBackedEnum::class, new Type('enum', className: DummyBackedEnum::class)];
        yield ['?'.DummyBackedEnum::class, new Type('enum', isNullable: true, className: DummyBackedEnum::class)];

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
        yield ['array', new Type('array')];
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

        // union types
        yield ['int|string', new Type('int|string', unionTypes: [new Type('int'), new Type('string')])];
        yield ['int|string|null', new Type('int|string|null', unionTypes: [new Type('int'), new Type('string'), new Type('null')])];
        yield [
            'array<string, string|float>|array<int, bool>',
            new Type(
                'array<string, string|float>|array<int, bool>',
                unionTypes: [
                    new Type(
                        'array',
                        isGeneric: true,
                        genericParameterTypes: [new Type('string'), new Type('string|float', unionTypes: [new Type('string'), new Type('float')])],
                    ),
                    new Type('array', isGeneric: true, genericParameterTypes: [new Type('int'), new Type('bool')]),
                ],
            ),
        ];
    }

    public function testCannotCreateGenericWithoutGenericTypes()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing generic parameter types of "object" type.');

        new Type('object', className: ClassicDummy::class, isGeneric: true, genericParameterTypes: []);
    }

    public function testCannotCreateUnionWithOnlyOneType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot define only one union type for "int" type.');

        new Type('int', unionTypes: [new Type('int')]);
    }

    public function testCannotGetClassNameOnNonObject()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot get class on "int" type as it\'s not an object nor an enum.');

        (new Type('int'))->className();
    }

    public function testGetCollectionKeyType()
    {
        $this->assertEquals(new Type('string'), (new Type('array', isGeneric: true, genericParameterTypes: [new Type('string'), new Type('int')]))->collectionKeyType());
        $this->assertEquals(new Type('mixed'), (new Type('array'))->collectionKeyType());
    }

    public function testCannotGetCollectionKeyTypeOnNonCollection()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot get collection key type on "int" type as it\'s not a collection.');

        (new Type('int'))->collectionKeyType();
    }

    public function testGetCollectionValueType()
    {
        $this->assertEquals(new Type('int'), (new Type('array', isGeneric: true, genericParameterTypes: [new Type('string'), new Type('int')]))->collectionValueType());
        $this->assertEquals(new Type('mixed'), (new Type('array'))->collectionValueType());
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
    public function testIsser(
        Type $type,
        bool $scalar,
        bool $null,
        bool $nullable,
        bool $object,
        bool $collection,
        bool $list,
        bool $dict,
        bool $generic,
        bool $class,
        bool $union,
    ) {
        $this->assertSame($scalar, $type->isScalar());
        $this->assertSame($null, $type->isNull());
        $this->assertSame($nullable, $type->isNullable());
        $this->assertSame($object, $type->isObject());
        $this->assertSame($collection, $type->isCollection());
        $this->assertSame($list, $type->isList());
        $this->assertSame($dict, $type->isDict());
        $this->assertSame($generic, $type->isGeneric());
        $this->assertSame($class, $type->hasClass());
        $this->assertSame($union, $type->isUnion());
    }

    /**
     * @return iterable<array{type: Type, scalar: bool, null: bool, nullable: bool, object: bool, collection: bool, list: bool, dict: bool, class: bool, union: bool}>
     */
    public static function isserDataProvider(): iterable
    {
        yield [
            'type' => new Type('int'),
            'scalar' => true,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => false,
        ];
        yield [
            'type' => new Type('string'),
            'scalar' => true,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => false,
        ];
        yield [
            'type' => new Type('bool'),
            'scalar' => true,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => false,
        ];
        yield [
            'type' => new Type('float'),
            'scalar' => true,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => false,
        ];
        yield [
            'type' => new Type('null'),
            'scalar' => true,
            'null' => true,
            'nullable' => false,
            'object' => false,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => false,
        ];
        yield [
            'type' => new Type('array'),
            'scalar' => false,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'collection' => true,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => false,
        ];
        yield [
            'type' => new Type('object'),
            'scalar' => false,
            'null' => false,
            'nullable' => false,
            'object' => true,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => false,
        ];
        yield [
            'type' => new Type('object', className: ClassicDummy::class),
            'scalar' => false,
            'null' => false,
            'nullable' => false,
            'object' => true,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => true,
            'union' => false,
        ];
        yield [
            'type' => new Type('object', className: ClassicDummy::class, isGeneric: true, genericParameterTypes: [new Type('int')]),
            'scalar' => false,
            'null' => false,
            'nullable' => false,
            'object' => true,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => true,
            'class' => true,
            'union' => false,
        ];
        yield [
            'type' => new Type('array', isGeneric: true, genericParameterTypes: [new Type('int'), new Type('int')]),
            'scalar' => false,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'collection' => true,
            'list' => true,
            'dict' => false,
            'generic' => true,
            'class' => false,
            'union' => false,
        ];
        yield [
            'type' => new Type('array', isGeneric: true, genericParameterTypes: [new Type('string'), new Type('int')]),
            'scalar' => false,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'collection' => true,
            'list' => false,
            'dict' => true,
            'generic' => true,
            'class' => false,
            'union' => false,
        ];
        yield [
            'type' => new Type('int|float', unionTypes: [new Type('int'), new Type('float')]),
            'scalar' => true,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => true,
        ];
        yield [
            'type' => new Type('int|float|null', unionTypes: [new Type('int'), new Type('float'), new Type('null')]),
            'scalar' => true,
            'null' => false,
            'nullable' => true,
            'object' => false,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => true,
        ];
        yield [
            'type' => new Type('int|array', unionTypes: [new Type('int'), new Type('array')]),
            'scalar' => false,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'collection' => false,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => true,
        ];
        yield [
            'type' => new Type('iterable|array', unionTypes: [new Type('iterable'), new Type('array')]),
            'scalar' => false,
            'null' => false,
            'nullable' => false,
            'object' => false,
            'collection' => true,
            'list' => false,
            'dict' => false,
            'generic' => false,
            'class' => false,
            'union' => true,
        ];
    }
}
