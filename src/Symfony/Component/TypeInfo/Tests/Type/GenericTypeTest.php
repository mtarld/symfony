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
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;

class GenericTypeTest extends TestCase
{
    public function testToString()
    {
        $type = new GenericType(new BuiltinType(BuiltinTypeEnum::ARRAY), new BuiltinType(BuiltinTypeEnum::BOOL));
        $this->assertEquals('array<bool>', (string) $type);

        $type = new GenericType(
            new BuiltinType(BuiltinTypeEnum::ARRAY),
            new BuiltinType(BuiltinTypeEnum::STRING),
            new BuiltinType(BuiltinTypeEnum::BOOL),
        );
        $this->assertEquals('array<string,bool>', (string) $type);

        $type = new GenericType(
            new ObjectType(self::class),
            new UnionType(new BuiltinType(BuiltinTypeEnum::BOOL), new BuiltinType(BuiltinTypeEnum::STRING)),
            new BuiltinType(BuiltinTypeEnum::INT),
            new BuiltinType(BuiltinTypeEnum::FLOAT),
        );
        $this->assertEquals(sprintf('%s<bool|string,int,float>', self::class), (string) $type);
    }

    public function testIsBuiltinType()
    {
        $type = new GenericType(
            new BuiltinType(BuiltinTypeEnum::ARRAY),
            new BuiltinType(BuiltinTypeEnum::STRING),
            new BuiltinType(BuiltinTypeEnum::BOOL),
        );
        $this->assertFalse($type->isNullable());
        $this->assertTrue($type->isBuiltinType(BuiltinTypeEnum::ARRAY));
        $this->assertFalse($type->isBuiltinType(BuiltinTypeEnum::STRING));

        $type = new GenericType(
            new ObjectType(self::class),
            new UnionType(new BuiltinType(BuiltinTypeEnum::BOOL), new BuiltinType(BuiltinTypeEnum::STRING)),
            new BuiltinType(BuiltinTypeEnum::INT),
            new BuiltinType(BuiltinTypeEnum::FLOAT),
        );
        $this->assertFalse($type->isNullable());
        $this->assertTrue($type->isBuiltinType(BuiltinTypeEnum::OBJECT));
        $this->assertFalse($type->isBuiltinType(BuiltinTypeEnum::INT));
        $this->assertFalse($type->isBuiltinType(BuiltinTypeEnum::STRING));
    }
}
