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
use Symfony\Component\TypeInfo\Type\IntersectionType;

class IntersectionTypeTest extends TestCase
{
    public function testCannotCreateWithOnlyOneType()
    {
        $this->expectException(InvalidArgumentException::class);
        new IntersectionType(Type::int());
    }

    public function testCannotCreateWithIntersectionTypeParts()
    {
        $this->expectException(InvalidArgumentException::class);
        new IntersectionType(Type::int(), new IntersectionType());
    }

    public function testSortTypesOnCreation()
    {
        $type = new IntersectionType(Type::int(), Type::string(), Type::bool());
        $this->assertEquals([Type::bool(), Type::int(), Type::string()], $type->getTypes());
    }

    public function testAtLeastOneTypeIs()
    {
        $type = new IntersectionType(Type::int(), Type::string(), Type::bool());

        $this->assertTrue($type->atLeastOneTypeIs(fn (Type $t) => 'int' === (string) $t));
        $this->assertFalse($type->atLeastOneTypeIs(fn (Type $t) => 'float' === (string) $t));
    }

    public function testEveryTypeIs()
    {
        $type = new IntersectionType(Type::int(), Type::string(), Type::bool());
        $this->assertTrue($type->everyTypeIs(fn (Type $t) => $t instanceof BuiltinType));

        $type = new IntersectionType(Type::int(), Type::string(), Type::template('T'));
        $this->assertFalse($type->everyTypeIs(fn (Type $t) => $t instanceof BuiltinType));
    }

    public function testToString()
    {
        $type = new IntersectionType(Type::int(), Type::string(), Type::float());
        $this->assertSame('float&int&string', (string) $type);

        $type = new IntersectionType(Type::int(), Type::string(), Type::union(Type::float(), Type::bool()));
        $this->assertSame('(bool|float)&int&string', (string) $type);
    }

    public function testIsBuiltinType()
    {
        $type = new IntersectionType(Type::int(), Type::string(), Type::float());
        $this->assertFalse($type->isNullable());
        $this->assertFalse($type->isBuiltinType(BuiltinTypeEnum::ARRAY));

        $type = new IntersectionType(Type::int(), Type::string(), Type::union(Type::float(), Type::bool()));
        $this->assertFalse($type->isNullable());
        $this->assertFalse($type->isBuiltinType(BuiltinTypeEnum::INT));
    }
}
