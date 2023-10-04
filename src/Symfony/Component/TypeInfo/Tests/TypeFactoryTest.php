<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\BuiltinType as BuiltinTypeEnum;
use Symfony\Component\TypeInfo\Exception\InvalidArgumentException;
use Symfony\Component\TypeInfo\Tests\Fixtures\DummyBackedEnum;
use Symfony\Component\TypeInfo\Tests\Fixtures\DummyEnum;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\EnumType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\IntersectionType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\TemplateType;
use Symfony\Component\TypeInfo\Type\UnionType;

class TypeFactoryTest extends TestCase
{
    public function testCreateBuiltin()
    {
        $this->assertEquals(new BuiltinType(BuiltinTypeEnum::INT), Type::builtin(BuiltinTypeEnum::INT));
        $this->assertEquals(new BuiltinType(BuiltinTypeEnum::INT), Type::builtin('int'));
        $this->assertEquals(new BuiltinType(BuiltinTypeEnum::INT), Type::int());
        $this->assertEquals(new BuiltinType(BuiltinTypeEnum::FLOAT), Type::float());
        $this->assertEquals(new BuiltinType(BuiltinTypeEnum::STRING), Type::string());
        $this->assertEquals(new BuiltinType(BuiltinTypeEnum::BOOL), Type::bool());
        $this->assertEquals(new BuiltinType(BuiltinTypeEnum::RESOURCE), Type::resource());
        $this->assertEquals(new BuiltinType(BuiltinTypeEnum::FALSE), Type::false());
        $this->assertEquals(new BuiltinType(BuiltinTypeEnum::TRUE), Type::true());
        $this->assertEquals(new BuiltinType(BuiltinTypeEnum::CALLABLE), Type::callable());
        $this->assertEquals(new BuiltinType(BuiltinTypeEnum::NULL), Type::null());
        $this->assertEquals(new BuiltinType(BuiltinTypeEnum::MIXED), Type::mixed());
    }

    public function testCreateArray()
    {
        $this->assertEquals(new CollectionType(new BuiltinType(BuiltinTypeEnum::ARRAY)), Type::array());

        $this->assertEquals(
            new CollectionType(new GenericType(
                new BuiltinType(BuiltinTypeEnum::ARRAY),
                new UnionType(new BuiltinType(BuiltinTypeEnum::INT), new BuiltinType(BuiltinTypeEnum::STRING)),
                new BuiltinType(BuiltinTypeEnum::BOOL),
            )),
            Type::array(Type::bool()),
        );

        $this->assertEquals(
            new CollectionType(new GenericType(
                new BuiltinType(BuiltinTypeEnum::ARRAY),
                new BuiltinType(BuiltinTypeEnum::STRING),
                new BuiltinType(BuiltinTypeEnum::BOOL),
            )),
            Type::array(Type::bool(), Type::string()),
        );

        $this->assertEquals(
            new CollectionType(new GenericType(
                new BuiltinType(BuiltinTypeEnum::ARRAY),
                new BuiltinType(BuiltinTypeEnum::INT),
                new BuiltinType(BuiltinTypeEnum::MIXED),
            )),
            Type::list(),
        );

        $this->assertEquals(
            new CollectionType(new GenericType(
                new BuiltinType(BuiltinTypeEnum::ARRAY),
                new BuiltinType(BuiltinTypeEnum::INT),
                new BuiltinType(BuiltinTypeEnum::BOOL),
            )),
            Type::list(Type::bool()),
        );

        $this->assertEquals(
            new CollectionType(new GenericType(
                new BuiltinType(BuiltinTypeEnum::ARRAY),
                new BuiltinType(BuiltinTypeEnum::STRING),
                new BuiltinType(BuiltinTypeEnum::MIXED),
            )),
            Type::dict(),
        );

        $this->assertEquals(
            new CollectionType(new GenericType(
                new BuiltinType(BuiltinTypeEnum::ARRAY),
                new BuiltinType(BuiltinTypeEnum::STRING),
                new BuiltinType(BuiltinTypeEnum::BOOL),
            )),
            Type::dict(Type::bool()),
        );
    }

    public function testCreateIterable()
    {
        $this->assertEquals(new CollectionType(new BuiltinType(BuiltinTypeEnum::ITERABLE)), Type::iterable());

        $this->assertEquals(
            new CollectionType(new GenericType(
                new BuiltinType(BuiltinTypeEnum::ITERABLE),
                new UnionType(new BuiltinType(BuiltinTypeEnum::INT), new BuiltinType(BuiltinTypeEnum::STRING)),
                new BuiltinType(BuiltinTypeEnum::BOOL),
            )),
            Type::iterable(Type::bool()),
        );

        $this->assertEquals(
            new CollectionType(new GenericType(
                new BuiltinType(BuiltinTypeEnum::ITERABLE),
                new BuiltinType(BuiltinTypeEnum::STRING),
                new BuiltinType(BuiltinTypeEnum::BOOL),
            )),
            Type::iterable(Type::bool(), Type::string()),
        );
    }

    public function testCreateObject()
    {
        $this->assertEquals(new BuiltinType(BuiltinTypeEnum::OBJECT), Type::object());
        $this->assertEquals(new ObjectType(self::class), Type::object(self::class));
    }

    public function testCreateEnum()
    {
        $this->assertEquals(new EnumType(DummyEnum::class), Type::enum(DummyEnum::class));
        $this->assertEquals(new BackedEnumType(DummyBackedEnum::class, new BuiltinType(BuiltinTypeEnum::STRING)), Type::enum(DummyBackedEnum::class));
        $this->assertEquals(
            new BackedEnumType(DummyBackedEnum::class, new BuiltinType(BuiltinTypeEnum::INT)),
            Type::enum(DummyBackedEnum::class, new BuiltinType(BuiltinTypeEnum::INT)),
        );
    }

    public function testCannotCreateUnitEnumWithBackingType()
    {
        $this->expectException(InvalidArgumentException::class);
        Type::enum(DummyEnum::class, new BuiltinType(BuiltinTypeEnum::INT));
    }

    public function testCreateGeneric()
    {
        $this->assertEquals(
            new GenericType(new ObjectType(self::class), new BuiltinType(BuiltinTypeEnum::INT)),
            Type::generic(Type::object(self::class), Type::int()),
        );
    }

    public function testCreateTemplate()
    {
        $this->assertEquals(new TemplateType('T'), Type::template('T'));
    }

    public function testCreateUnion()
    {
        $this->assertEquals(new UnionType(new BuiltinType(BuiltinTypeEnum::INT), new ObjectType(self::class)), Type::union(Type::int(), Type::object(self::class)));
        $this->assertEquals(new UnionType(new BuiltinType(BuiltinTypeEnum::INT), new BuiltinType(BuiltinTypeEnum::STRING)), Type::union(Type::int(), Type::string(), Type::int()));
        $this->assertEquals(new UnionType(new BuiltinType(BuiltinTypeEnum::INT), new BuiltinType(BuiltinTypeEnum::STRING)), Type::union(Type::int(), Type::union(Type::int(), Type::string())));
    }

    public function testCreateIntersection()
    {
        $this->assertEquals(new IntersectionType(new BuiltinType(BuiltinTypeEnum::INT), new ObjectType(self::class)), Type::intersection(Type::int(), Type::object(self::class)));
        $this->assertEquals(new IntersectionType(new BuiltinType(BuiltinTypeEnum::INT), new BuiltinType(BuiltinTypeEnum::STRING)), Type::intersection(Type::int(), Type::string(), Type::int()));
        $this->assertEquals(new IntersectionType(new BuiltinType(BuiltinTypeEnum::INT), new BuiltinType(BuiltinTypeEnum::STRING)), Type::intersection(Type::int(), Type::intersection(Type::int(), Type::string())));
    }

    public function testCreateNullable()
    {
        $this->assertEquals(new UnionType(new BuiltinType(BuiltinTypeEnum::INT), new BuiltinType(BuiltinTypeEnum::NULL)), Type::nullable(Type::int()));
        $this->assertEquals(new UnionType(new BuiltinType(BuiltinTypeEnum::INT), new BuiltinType(BuiltinTypeEnum::NULL)), Type::nullable(Type::nullable(Type::int())));

        $this->assertEquals(
            new UnionType(new BuiltinType(BuiltinTypeEnum::INT), new BuiltinType(BuiltinTypeEnum::STRING), new BuiltinType(BuiltinTypeEnum::NULL)),
            Type::nullable(Type::union(Type::int(), Type::string())),
        );
        $this->assertEquals(
            new UnionType(new BuiltinType(BuiltinTypeEnum::INT), new BuiltinType(BuiltinTypeEnum::STRING), new BuiltinType(BuiltinTypeEnum::NULL)),
            Type::nullable(Type::union(Type::int(), Type::string(), Type::null())),
        );
    }
}
