<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Tests\Marshal\Mapping;

use PHPUnit\Framework\TestCase;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\AttributePropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\PropertyMetadata;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithFormatterAttributes;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithMaxDepthAttribute;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithNameAttributes;
use Symfony\Component\JsonMarshaller\Type\PhpstanTypeExtractor;
use Symfony\Component\JsonMarshaller\Type\ReflectionTypeExtractor;
use Symfony\Component\JsonMarshaller\Type\Type;

class AttributePropertyMetadataLoaderTest extends TestCase
{
    public function testRetrieveMarshalledName()
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());
        $loader = new AttributePropertyMetadataLoader(new PropertyMetadataLoader($typeExtractor), $typeExtractor);

        $this->assertSame(['@id', 'name'], array_keys($loader->load(DummyWithNameAttributes::class, [], [])));
    }

    public function testRetrieveMarshalFormatter()
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());
        $loader = new AttributePropertyMetadataLoader(new PropertyMetadataLoader($typeExtractor), $typeExtractor);

        $this->assertEquals([
            'id' => new PropertyMetadata('id', Type::string(), [DummyWithFormatterAttributes::doubleAndCastToString(...)]),
            'name' => new PropertyMetadata('name', Type::string(), []),
        ], $loader->load(DummyWithFormatterAttributes::class, [], []));
    }

    public function testRetrieveMaxDepthFormatter()
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());
        $loader = new AttributePropertyMetadataLoader(new PropertyMetadataLoader($typeExtractor), $typeExtractor);

        $this->assertEquals([
            'id' => new PropertyMetadata('id', Type::int(), []),
        ], $loader->load(DummyWithMaxDepthAttribute::class, [], []));

        $this->assertEquals([
            'id' => new PropertyMetadata('id', Type::bool(), [DummyWithMaxDepthAttribute::boolean(...)]),
        ], $loader->load(DummyWithMaxDepthAttribute::class, [], [
            'depth_counters' => [DummyWithMaxDepthAttribute::class => 256],
        ]));
    }
}
