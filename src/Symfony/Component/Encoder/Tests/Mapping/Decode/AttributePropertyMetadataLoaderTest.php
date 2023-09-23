<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\Tests\Mapping\Decode;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Encoder\Mapping\Decode\AttributePropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\PropertyMetadata;
use Symfony\Component\Encoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\Encoder\Tests\Fixtures\Model\DummyWithFormatterAttributes;
use Symfony\Component\Encoder\Tests\Fixtures\Model\DummyWithNameAttributes;
use Symfony\Component\Encoder\Tests\TypeResolverAwareTrait;
use Symfony\Component\TypeInfo\Type;

class AttributePropertyMetadataLoaderTest extends TestCase
{
    use TypeResolverAwareTrait;

    public function testRetrieveEncodedName()
    {
        $typeResolver = self::getTypeResolver();
        $loader = new AttributePropertyMetadataLoader(new PropertyMetadataLoader($typeResolver), $typeResolver);

        $this->assertSame(['@id', 'name'], array_keys($loader->load(DummyWithNameAttributes::class, [], [])));
    }

    public function testRetrieveDecodeFormatter()
    {
        $typeResolver = self::getTypeResolver();
        $loader = new AttributePropertyMetadataLoader(new PropertyMetadataLoader($typeResolver), $typeResolver);

        $this->assertEquals([
            'id' => new PropertyMetadata('id', Type::string(), [DummyWithFormatterAttributes::divideAndCastToInt(...)]),
            'name' => new PropertyMetadata('name', Type::string(), []),
        ], $loader->load(DummyWithFormatterAttributes::class, [], []));
    }
}
