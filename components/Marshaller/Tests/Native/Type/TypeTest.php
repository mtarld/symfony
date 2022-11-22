<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Type\Type;
use Symfony\Component\Marshaller\Native\Type\UnionType;

final class TypeTest extends TestCase
{
    /**
     * @dataProvider createFromStringDataProvider
     */
    public function testCreateFromString(Type|UnionType $expectedType, string $string): void
    {
        $this->assertEquals($expectedType, Type::createFromString($string));
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
        yield [new Type('object', className: self::class), self::class];
        yield [new Type('object', isNullable: true, className: self::class), '?'.self::class];

        // collection types
        yield [new Type('array', collectionKeyType: new Type('int'), collectionValueType: new Type('int')), 'array<int, int>'];
        yield [new Type('array', collectionKeyType: new Type('int'), collectionValueType: new Type('float')), 'array<float>'];
        yield [
            new Type(
                'array',
                collectionKeyType: new Type('string'),
                collectionValueType: new Type('array', collectionKeyType: new Type('int'), collectionValueType: new Type('bool')),
            ),
            'array<string, array<int, bool>>',
        ];
        yield [
            new Type(
                'array',
                isNullable: true,
                collectionKeyType: new Type('string', isNullable: true),
                collectionValueType: new Type(
                    'array',
                    isNullable: true,
                    collectionKeyType: new Type('int', isNullable: true),
                    collectionValueType: new Type('bool', isNullable: true),
                ),
            ),
            '?array<?string, ?array<?int, ?bool>>',
        ];

        // union types
        yield [new UnionType([new Type('int'), new Type('string')]), 'int|string'];
        yield [new UnionType([new Type('int'), new Type('string'), new Type('null')]), 'int|string|null'];
        yield [new UnionType([new Type('int'), new Type('string'), new Type('null')]), 'int|?string|null'];
        yield [
            new UnionType([
                new Type(
                    'array',
                    collectionKeyType: new Type('string'),
                    collectionValueType: new UnionType([new Type('string'), new Type('float')]),
                ),
                new Type('array', collectionKeyType: new Type('int'), collectionValueType: new Type('bool')),
            ]),
            'array<string, string|float>|array<int, bool>',
        ];
    }

    public function testCreateThrowOnIntersectionTypes(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot handle intersection types.');

        Type::createFromString('foo&bar');
    }

    public function testCreateThrowOnInvalidArrayString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "array<int, array<string, bool>" type.');

        Type::createFromString('array<int, array<string, bool>');
    }

    public function testCreateThrowOnUnknownType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "foo" type.');

        Type::createFromString('foo');
    }

    /**
     * @dataProvider toStringDataProvider
     */
    public function testToString(string $expectedString, Type|UnionType $type): void
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
        yield [self::class, new Type('object', className: self::class)];
        yield ['?'.self::class, new Type('object', isNullable: true, className: self::class)];

        // collection types
        yield ['array<int, int>', new Type('array', collectionKeyType: new Type('int'), collectionValueType: new Type('int'))];
        yield ['array<int, float>', new Type('array', collectionKeyType: new Type('int'), collectionValueType: new Type('float'))];
        yield [
            'array<string, array<int, bool>>',
            new Type(
                'array',
                collectionKeyType: new Type('string'),
                collectionValueType: new Type('array', collectionKeyType: new Type('int'), collectionValueType: new Type('bool')),
            ),
        ];
        yield [
            '?array<?string, ?array<?int, ?bool>>',
            new Type(
                'array',
                isNullable: true,
                collectionKeyType: new Type('string', isNullable: true),
                collectionValueType: new Type(
                    'array',
                    isNullable: true,
                    collectionKeyType: new Type('int', isNullable: true),
                    collectionValueType: new Type('bool', isNullable: true),
                ),
            ),
        ];
    }

    /**
     * @dataProvider validatorDataProvider
     */
    public function testValidator(string $expectedValidator, Type $type): void
    {
        $this->assertSame($expectedValidator, $type->validator('$accessor'));
    }

    /**
     * @return iterable<array{0: string, 1: Type}>
     */
    public function validatorDataProvider(): iterable
    {
        // scalar types
        yield ['null === $accessor', new Type('null')];
        yield ['is_int($accessor)', new Type('int')];
        yield ['is_string($accessor)', new Type('string')];
        yield ['is_float($accessor)', new Type('float')];
        yield ['is_bool($accessor)', new Type('bool')];

        // object types
        yield [sprintf('$accessor instanceof %s', self::class), new Type('object', className: self::class)];

        // collection types
        yield ['is_array($accessor) && array_is_list($accessor)', new Type('array', collectionKeyType: new Type('int'), collectionValueType: new Type('int'))];
        yield ['is_array($accessor) && !array_is_list($accessor)', new Type('array', collectionKeyType: new Type('string'), collectionValueType: new Type('int'))];
    }

    public function testThrowOnUnavailableValidator(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot find validator for "foo"');

        (new Type('foo'))->validator('$accessor');
    }

    public function testCannotCreateObjectWithoutClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing className of "object" type.');

        new Type('object');
    }

    public function testCannotCreateWithTypeAndWithoutValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing either collectionKeyType or collectionValueType of "array" type.');

        new Type('array', collectionValueType: new Type('int'));
    }

    public function testCannotCreateWithValueAndWithoutType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing either collectionKeyType or collectionValueType of "array" type.');

        new Type('array', collectionKeyType: new Type('int'));
    }

    public function testCannotGetClassNameOnNonObject(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot get class on "int" type as it\'s not an object.');

        (new Type('int'))->className();
    }

    public function testCannotGetCollectionKeyTypeOnNonCollection(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot get collection key type on "int" type as it\'s not a collection');

        (new Type('int'))->collectionKeyType();
    }

    public function testCannotGetCollectionValueTypeOnNonCollection(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot get collection value type on "int" type as it\'s not a collection');

        (new Type('int'))->collectionValueType();
    }

    /**
     * @dataProvider isserDataProvider
     */
    public function testIsser(Type $type, bool $scalar, bool $nullable, bool $object): void
    {
        $this->assertSame($scalar, $type->isScalar());
        $this->assertSame($nullable, $type->isNull());
        $this->assertSame($object, $type->isObject());
    }

    /**
     * @return iterable<array{type: Type, scalar: bool, null: bool, object: bool, collection: bool, list: bool, dict: bool}>
     */
    public function isserDataProvider(): iterable
    {
        yield ['type' => new Type('int'), 'scalar' => true, 'null' => false, 'object' => false, 'collection' => false, 'list' => false, 'dict' => false];
        yield ['type' => new Type('string'), 'scalar' => true, 'null' => false, 'object' => false, 'collection' => false, 'list' => false, 'dict' => false];
        yield ['type' => new Type('bool'), 'scalar' => true, 'null' => false, 'object' => false, 'collection' => false, 'list' => false, 'dict' => false];
        yield ['type' => new Type('float'), 'scalar' => true, 'null' => false, 'object' => false, 'collection' => false, 'list' => false, 'dict' => false];
        yield ['type' => new Type('null'), 'scalar' => false, 'null' => true, 'object' => false, 'collection' => false, 'list' => false, 'dict' => false];
        yield ['type' => new Type('object', className: self::class), 'scalar' => false, 'null' => false, 'object' => true, 'collection' => false, 'list' => false, 'dict' => false];
        yield [
            'type' => new Type('array', collectionKeyType: new Type('int'), collectionValueType: new Type('int')),
            'scalar' => false,
            'null' => false,
            'object' => false,
            'collection' => true,
            'list' => true,
            'dict' => false,
        ];
        yield [
            'type' => new Type('array', collectionKeyType: new Type('string'), collectionValueType: new Type('int')),
            'scalar' => false,
            'null' => false,
            'object' => false,
            'collection' => true,
            'list' => false,
            'dict' => true,
        ];
    }
}
