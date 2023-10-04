<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo\Tests\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\BuiltinType as BuiltinTypeEnum;
use Symfony\Component\TypeInfo\Exception\InvalidArgumentException;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\UnionType;

class UnionTypeTest extends TestCase
{
    public function testCannotCreateWithOnlyOneType()
    {
        $this->expectException(InvalidArgumentException::class);
        new UnionType(Type::int());
    }

    public function testCannotCreateWithUnionTypeParts()
    {
        $this->expectException(InvalidArgumentException::class);
        new UnionType(Type::int(), new UnionType());
    }

    public function testSortTypesOnCreation()
    {
        $type = new UnionType(Type::int(), Type::string(), Type::bool());
        $this->assertEquals([Type::bool(), Type::int(), Type::string()], $type->getTypes());
    }

    public function testAsNonNullable()
    {
        $type = new UnionType(Type::int(), Type::string(), Type::bool());
        $this->assertInstanceOf(UnionType::class, $type->asNonNullable());
        $this->assertEquals([Type::bool(), Type::int(), Type::string()], $type->asNonNullable()->getTypes());

        $type = new UnionType(Type::int(), Type::string(), Type::null());
        $this->assertInstanceOf(UnionType::class, $type->asNonNullable());
        $this->assertEquals([Type::int(), Type::string()], $type->asNonNullable()->getTypes());

        $type = new UnionType(Type::int(), Type::null());
        $this->assertInstanceOf(BuiltinType::class, $type->asNonNullable());
        $this->assertEquals(Type::int(), $type->asNonNullable());
    }

    public function testAtLeastOneTypeIs()
    {
        $type = new UnionType(Type::int(), Type::string(), Type::bool());

        $this->assertTrue($type->atLeastOneTypeIs(fn (Type $t) => 'int' === (string) $t));
        $this->assertFalse($type->atLeastOneTypeIs(fn (Type $t) => 'float' === (string) $t));
    }

    public function testEveryTypeIs()
    {
        $type = new UnionType(Type::int(), Type::string(), Type::bool());
        $this->assertTrue($type->everyTypeIs(fn (Type $t) => $t instanceof BuiltinType));

        $type = new UnionType(Type::int(), Type::string(), Type::template('T'));
        $this->assertFalse($type->everyTypeIs(fn (Type $t) => $t instanceof BuiltinType));
    }

    public function testToString()
    {
        $type = new UnionType(Type::int(), Type::string(), Type::float());
        $this->assertSame('float|int|string', (string) $type);

        $type = new UnionType(Type::int(), Type::string(), Type::intersection(Type::float(), Type::bool()));
        $this->assertSame('(bool&float)|int|string', (string) $type);
    }

    public function testIsBuiltinType()
    {
        $type = new UnionType(Type::int(), Type::string(), Type::float());
        $this->assertFalse($type->isNullable());
        $this->assertFalse($type->isBuiltinType(BuiltinTypeEnum::ARRAY));

        $type = new UnionType(Type::int(), Type::string(), Type::intersection(Type::float(), Type::bool()));
        $this->assertFalse($type->isNullable());
        $this->assertTrue($type->isBuiltinType(BuiltinTypeEnum::INT));
        $this->assertTrue($type->isBuiltinType(BuiltinTypeEnum::STRING));
        $this->assertFalse($type->isBuiltinType(BuiltinTypeEnum::FLOAT));
        $this->assertFalse($type->isBuiltinType(BuiltinTypeEnum::BOOL));
    }
}
