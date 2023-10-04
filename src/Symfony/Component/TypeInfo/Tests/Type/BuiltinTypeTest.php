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

class BuiltinTypeTest extends TestCase
{
    public function testToString()
    {
        $this->assertSame('int', (string) new BuiltinType(BuiltinTypeEnum::INT));
    }

    public function testIsBuiltinType()
    {
        $this->assertFalse((new BuiltinType(BuiltinTypeEnum::INT))->isBuiltinType(BuiltinTypeEnum::ARRAY));
        $this->assertTrue((new BuiltinType(BuiltinTypeEnum::INT))->isBuiltinType(BuiltinTypeEnum::INT));
        $this->assertFalse((new BuiltinType(BuiltinTypeEnum::INT))->isNullable());
        $this->assertTrue((new BuiltinType(BuiltinTypeEnum::NULL))->isNullable());
        $this->assertTrue((new BuiltinType(BuiltinTypeEnum::MIXED))->isNullable());
    }
}
