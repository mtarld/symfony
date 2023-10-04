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
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\GenericType;

class CollectionTypeTest extends TestCase
{
    public function testGetCollectionKeyType()
    {
        $type = new CollectionType(new BuiltinType(BuiltinTypeEnum::ARRAY));
        $this->assertEquals(Type::union(Type::int(), Type::string()), $type->getCollectionKeyType());

        $type = new CollectionType(new GenericType(new BuiltinType(BuiltinTypeEnum::ARRAY), new BuiltinType(BuiltinTypeEnum::BOOL)));
        $this->assertEquals(Type::int(), $type->getCollectionKeyType());

        $type = new CollectionType(new GenericType(
            new BuiltinType(BuiltinTypeEnum::ARRAY),
            new BuiltinType(BuiltinTypeEnum::STRING),
            new BuiltinType(BuiltinTypeEnum::BOOL),
        ));
        $this->assertEquals(Type::string(), $type->getCollectionKeyType());
    }

    public function testGetCollectionValueType()
    {
        $type = new CollectionType(new BuiltinType(BuiltinTypeEnum::ARRAY));
        $this->assertEquals(Type::mixed(), $type->getCollectionValueType());

        $type = new CollectionType(new GenericType(new BuiltinType(BuiltinTypeEnum::ARRAY), new BuiltinType(BuiltinTypeEnum::BOOL)));
        $this->assertEquals(Type::bool(), $type->getCollectionValueType());

        $type = new CollectionType(new GenericType(
            new BuiltinType(BuiltinTypeEnum::ARRAY),
            new BuiltinType(BuiltinTypeEnum::STRING),
            new BuiltinType(BuiltinTypeEnum::BOOL),
        ));
        $this->assertEquals(Type::bool(), $type->getCollectionValueType());
    }

    public function testToString()
    {
        $type = new CollectionType(new BuiltinType(BuiltinTypeEnum::ITERABLE));
        $this->assertEquals('iterable', (string) $type);

        $type = new CollectionType(new GenericType(new BuiltinType(BuiltinTypeEnum::ARRAY), new BuiltinType(BuiltinTypeEnum::BOOL)));
        $this->assertEquals('array<bool>', (string) $type);

        $type = new CollectionType(new GenericType(
            new BuiltinType(BuiltinTypeEnum::ARRAY),
            new BuiltinType(BuiltinTypeEnum::STRING),
            new BuiltinType(BuiltinTypeEnum::BOOL),
        ));
        $this->assertEquals('array<string,bool>', (string) $type);
    }

    public function testIsBuiltinType()
    {
        $type = new CollectionType(new GenericType(
            new BuiltinType(BuiltinTypeEnum::ARRAY),
            new BuiltinType(BuiltinTypeEnum::STRING),
            new BuiltinType(BuiltinTypeEnum::BOOL),
        ));

        $this->assertTrue($type->isBuiltinType(BuiltinTypeEnum::ARRAY));
        $this->assertFalse($type->isBuiltinType(BuiltinTypeEnum::STRING));
        $this->assertFalse($type->isBuiltinType(BuiltinTypeEnum::INT));
        $this->assertFalse($type->isNullable());
    }
}
