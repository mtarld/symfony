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
use Symfony\Component\TypeInfo\Tests\Fixtures\DummyEnum;
use Symfony\Component\TypeInfo\Type\EnumType;

class EnumTypeTest extends TestCase
{
    public function testCannotCreateWithInvalidClass()
    {
        $this->expectException(InvalidArgumentException::class);
        new EnumType(self::class);
    }

    public function testToString()
    {
        $this->assertSame(DummyEnum::class, (string) new EnumType(DummyEnum::class));
    }

    public function testIsBuiltinType()
    {
        $this->assertFalse((new EnumType(DummyEnum::class))->isNullable());
        $this->assertFalse((new EnumType(DummyEnum::class))->isBuiltinType(BuiltinTypeEnum::ARRAY));
        $this->assertFalse((new EnumType(DummyEnum::class))->isBuiltinType(BuiltinTypeEnum::INT));
    }
}
