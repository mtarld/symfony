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
use Symfony\Component\TypeInfo\BuiltinType;
use Symfony\Component\TypeInfo\Exception\LogicException;
use Symfony\Component\TypeInfo\Type;

class TypeTest extends TestCase
{
    public function testIs()
    {
        $isInt = fn (Type $t) => BuiltinType::INT === $t->getBaseType()->getBuiltinType();

        $this->assertTrue(Type::int()->is($isInt));
        $this->assertTrue(Type::union(Type::string(), Type::int())->is($isInt));
        $this->assertTrue(Type::generic(Type::int(), Type::string())->is($isInt));

        $this->assertFalse(Type::string()->is($isInt));
        $this->assertFalse(Type::union(Type::string(), Type::float())->is($isInt));
        $this->assertFalse(Type::generic(Type::string(), Type::int())->is($isInt));
    }

    public function testIsBuiltinType()
    {
        $this->assertTrue(Type::int()->isBuiltinType(BuiltinType::INT));
        $this->assertTrue(Type::union(Type::string(), Type::int())->isBuiltinType(BuiltinType::INT));
        $this->assertTrue(Type::generic(Type::int(), Type::string())->isBuiltinType(BuiltinType::INT));

        $this->assertFalse(Type::string()->isBuiltinType(BuiltinType::INT));
        $this->assertFalse(Type::union(Type::string(), Type::float())->isBuiltinType(BuiltinType::INT));
        $this->assertFalse(Type::generic(Type::string(), Type::int())->isBuiltinType(BuiltinType::INT));
    }

    public function testIsNullable()
    {
        $this->assertTrue(Type::null()->isNullable());
        $this->assertTrue(Type::mixed()->isNullable());
        $this->assertTrue(Type::nullable(Type::int())->isNullable());
        $this->assertTrue(Type::union(Type::int(), Type::null())->isNullable());
        $this->assertTrue(Type::union(Type::int(), Type::mixed())->isNullable());
        $this->assertTrue(Type::generic(Type::mixed(), Type::string())->isNullable());

        $this->assertFalse(Type::int()->isNullable());
        $this->assertFalse(Type::int()->isNullable());
        $this->assertFalse(Type::union(Type::int(), Type::string())->isNullable());
        $this->assertFalse(Type::generic(Type::int(), Type::nullable(Type::string()))->isNullable());
        $this->assertFalse(Type::generic(Type::int(), Type::mixed())->isNullable());
    }

    public function testGetBaseType()
    {
        $this->assertEquals(Type::string(), Type::string()->getBaseType());
        $this->assertEquals(Type::object(self::class), Type::object(self::class)->getBaseType());
        $this->assertEquals(Type::object(), Type::generic(Type::object(), Type::int())->getBaseType());
        $this->assertEquals(Type::builtin(BuiltinType::ARRAY), Type::list()->getBaseType());
        $this->assertEquals(Type::int(), Type::collection(Type::generic(Type::int(), Type::string()))->getBaseType());
    }

    public function testCannotGetBaseTypeOnCompoundType()
    {
        $this->expectException(LogicException::class);
        Type::union(Type::int(), Type::string())->getBaseType();
    }
}
