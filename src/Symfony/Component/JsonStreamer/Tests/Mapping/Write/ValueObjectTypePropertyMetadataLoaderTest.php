<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonStreamer\Tests\Mapping\Write;

use PHPUnit\Framework\TestCase;
use Symfony\Component\JsonStreamer\Mapping\PropertyMetadata;
use Symfony\Component\JsonStreamer\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\JsonStreamer\Mapping\Write\ValueObjectTypePropertyMetadataLoader;
use Symfony\Component\TypeInfo\Type;

class ValueObjectTypePropertyMetadataLoaderTest extends TestCase
{
    public function testAddValueTransformer()
    {
        $loader = new ValueObjectTypePropertyMetadataLoader(self::propertyMetadataLoader([
            'valueObject' => new PropertyMetadata('valueObject', Type::object(\DateTimeImmutable::class)),
            'other' => new PropertyMetadata('other', Type::object(self::class)),
        ]));

        $this->assertEquals([
            'valueObject' => new PropertyMetadata('valueObject', Type::string(), ['json_streamer.value_transformer.value_object_to_scalar']),
            'other' => new PropertyMetadata('other', Type::object(self::class)),
        ], $loader->load(self::class));
    }

    /**
     * @param array<string, PropertyMetadata> $propertiesMetadata
     */
    private static function propertyMetadataLoader(array $propertiesMetadata = []): PropertyMetadataLoaderInterface
    {
        return new class($propertiesMetadata) implements PropertyMetadataLoaderInterface {
            public function __construct(private array $propertiesMetadata)
            {
            }

            public function load(string $className, array $options = [], array $context = []): array
            {
                return $this->propertiesMetadata;
            }
        };
    }
}
