<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\Tests\Mapping;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Encoder\Mapping\GenericTypePropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\PropertyMetadata;
use Symfony\Component\Encoder\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\Encoder\Tests\Fixtures\Model\DummyWithGenerics;
use Symfony\Component\TypeInfo\Type;

class GenericTypePropertyMetadataLoaderTest extends TestCase
{
    public function testReplaceGenerics()
    {
        $loader = new GenericTypePropertyMetadataLoader(self::propertyMetadataLoader([
            'foo' => new PropertyMetadata('foo', new Type('T'), []),
        ]));

        $metadata = $loader->load(
            DummyWithGenerics::class,
            [],
            ['original_type' => Type::generic(Type::object(DummyWithGenerics::class), Type::int())],
        );

        $this->assertEquals([
            'foo' => new PropertyMetadata('foo', Type::int(), []),
        ], $metadata);
    }

    /**
     * @param array<string, PropertyMetadata> $propertiesMetadata
     */
    private static function propertyMetadataLoader(array $propertiesMetadata = []): PropertyMetadataLoaderInterface
    {
        return new class($propertiesMetadata) implements PropertyMetadataLoaderInterface {
            public function __construct(private readonly array $propertiesMetadata)
            {
            }

            public function load(string $className, array $config, array $context): array
            {
                return $this->propertiesMetadata;
            }
        };
    }
}
