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
use Symfony\Component\TypeInfo\Tests\Fixtures\DummyBackedEnum;
use Symfony\Component\TypeInfo\Tests\Fixtures\DummyEnum;
use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Symfony\Component\TypeInfo\Type\BuiltinType;

class BackedEnumTypeTest extends TestCase
{
    public function testCannotCreateWithInvalidClass()
    {
        $this->expectException(InvalidArgumentException::class);
        new BackedEnumType(DummyEnum::class, new BuiltinType(BuiltinTypeEnum::INT));
    }

    public function testCannotCreateWithInvalidBackingType()
    {
        $this->expectException(InvalidArgumentException::class);
        new BackedEnumType(DummyBackedEnum::class, new BuiltinType(BuiltinTypeEnum::BOOL));
    }

    public function testToString()
    {
        $this->assertSame(DummyBackedEnum::class, (string) new BackedEnumType(DummyBackedEnum::class, new BuiltinType(BuiltinTypeEnum::INT)));
    }

    public function testIsBuiltinType()
    {
        $this->assertFalse((new BackedEnumType(DummyBackedEnum::class, new BuiltinType(BuiltinTypeEnum::INT)))->isNullable());
        $this->assertFalse((new BackedEnumType(DummyBackedEnum::class, new BuiltinType(BuiltinTypeEnum::INT)))->isBuiltinType(BuiltinTypeEnum::ARRAY));
        $this->assertTrue((new BackedEnumType(DummyBackedEnum::class, new BuiltinType(BuiltinTypeEnum::INT)))->isBuiltinType(BuiltinTypeEnum::OBJECT));
    }
}
