<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Tests\Unmarshal\Mapping;

use PHPUnit\Framework\TestCase;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithGenerics;
use Symfony\Component\JsonMarshaller\Type\PhpstanTypeExtractor;
use Symfony\Component\JsonMarshaller\Type\Type;
use Symfony\Component\JsonMarshaller\Type\TypeExtractorInterface;
use Symfony\Component\JsonMarshaller\Unmarshal\Mapping\PropertyMetadata;
use Symfony\Component\JsonMarshaller\Unmarshal\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\JsonMarshaller\Unmarshal\Mapping\TypePropertyMetadataLoader;

class TypePropertyMetadataLoaderTest extends TestCase
{
    public function testCastStringToDateTime()
    {
        $loader = new TypePropertyMetadataLoader(self::propertyMetadataLoader([
            'foo' => new PropertyMetadata('foo', Type::class(\DateTimeImmutable::class), []),
        ]), $this->createStub(TypeExtractorInterface::class));

        $metadata = $loader->load(self::class, [], ['original_type' => Type::fromString('useless')]);

        $this->assertEquals([
            'foo' => new PropertyMetadata('foo', Type::string(), [
                \Closure::fromCallable(TypePropertyMetadataLoader::castStringToDateTime(...)),
            ]),
        ], $metadata);

        $formatter = $metadata['foo']->formatters()[0];

        $this->assertEquals(
            new \DateTimeImmutable('2023-07-26'),
            $formatter('2023-07-26', []),
        );

        $this->assertEquals(
            (new \DateTimeImmutable('2023-07-26'))->setTime(0, 0),
            $formatter('26/07/2023 00:00:00', ['date_time_format' => 'd/m/Y H:i:s']),
        );
    }

    public function testReplaceGenerics()
    {
        $loader = new TypePropertyMetadataLoader(self::propertyMetadataLoader([
            'foo' => new PropertyMetadata('foo', Type::fromString('T'), []),
        ]), new PhpstanTypeExtractor($this->createStub(TypeExtractorInterface::class)));

        $metadata = $loader->load(
            DummyWithGenerics::class,
            [],
            ['original_type' => Type::generic(Type::class(DummyWithGenerics::class), Type::int())],
        );

        $this->assertEquals([
            'foo' => new PropertyMetadata('foo', Type::int(), []),
        ], $metadata);
    }

    public function testReplaceGenericsAndCastStringToDateTime()
    {
        $loader = new TypePropertyMetadataLoader(self::propertyMetadataLoader([
            'foo' => new PropertyMetadata('foo', Type::fromString('T'), []),
        ]), new PhpstanTypeExtractor($this->createStub(TypeExtractorInterface::class)));

        $metadata = $loader->load(
            DummyWithGenerics::class,
            [],
            ['original_type' => Type::generic(Type::class(DummyWithGenerics::class), Type::class(\DateTimeImmutable::class))],
        );

        $this->assertEquals([
            'foo' => new PropertyMetadata('foo', Type::string(), [
                \Closure::fromCallable(TypePropertyMetadataLoader::castStringToDateTime(...)),
            ]),
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
