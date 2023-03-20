<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Tests\Mapping;

use PHPUnit\Framework\TestCase;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadata;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\ClassicDummy;
use Symfony\Component\JsonEncoder\Tests\TypeResolverAwareTrait;
use Symfony\Component\TypeInfo\Type;

class PropertyMetadataLoaderTest extends TestCase
{
    use TypeResolverAwareTrait;

    public function testReadPropertyType()
    {
        $loader = new PropertyMetadataLoader(self::getTypeResolver());

        $this->assertEquals([
            'id' => new PropertyMetadata('id', Type::int(), []),
            'name' => new PropertyMetadata('name', Type::string(), []),
        ], $loader->load(ClassicDummy::class, [], []));
    }
}
