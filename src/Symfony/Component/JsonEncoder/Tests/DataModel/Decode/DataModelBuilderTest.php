<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Tests\DataModel\Decode;

use PHPUnit\Framework\TestCase;
use Symfony\Component\JsonEncoder\DataModel\Decode\BackedEnumNode;
use Symfony\Component\JsonEncoder\DataModel\Decode\CollectionNode;
use Symfony\Component\JsonEncoder\DataModel\Decode\CompositeNode;
use Symfony\Component\JsonEncoder\DataModel\Decode\DataModelBuilder;
use Symfony\Component\JsonEncoder\DataModel\Decode\DataModelNodeInterface;
use Symfony\Component\JsonEncoder\DataModel\Decode\ObjectNode;
use Symfony\Component\JsonEncoder\DataModel\Decode\ScalarNode;
use Symfony\Component\JsonEncoder\DataModel\FunctionDataAccessor;
use Symfony\Component\JsonEncoder\DataModel\ScalarDataAccessor;
use Symfony\Component\JsonEncoder\DataModel\VariableDataAccessor;
use Symfony\Component\JsonEncoder\Exception\UnsupportedException;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadata;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Enum\DummyEnum;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNormalizerAttributes;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithUnionProperties;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;

class DataModelBuilderTest extends TestCase
{
    /**
     * @dataProvider buildDataModelDataProvider
     */
    public function testBuildDataModel(DataModelNodeInterface $dataModel, Type $type)
    {
        $typeResolver = TypeResolver::create();
        $dataModelBuilder = new DataModelBuilder(new PropertyMetadataLoader($typeResolver));

        $this->assertEquals($dataModel, $dataModelBuilder->build($type, []));
    }

    /**
     * @return iterable<array{0: DataModelNodeInterface, 1: Type}>
     */
    public static function buildDataModelDataProvider(): iterable
    {
        yield [new ScalarNode(Type::int()), Type::int()];
        yield [new CompositeNode([new ScalarNode(Type::int()), new ScalarNode(Type::null())]), Type::nullable(Type::int())];
        yield [new ScalarNode(Type::builtin(TypeIdentifier::ARRAY)), Type::builtin(TypeIdentifier::ARRAY)];
        yield [new ScalarNode(Type::object()), Type::object()];
        yield [new ScalarNode(Type::null()), Type::null()];

        yield [new CollectionNode(Type::array(Type::string()), new ScalarNode(Type::string())), Type::array(Type::string())];
        yield [new CollectionNode(Type::list(Type::string()), new ScalarNode(Type::string())), Type::list(Type::string())];
        yield [new CollectionNode(Type::dict(Type::string()), new ScalarNode(Type::string())), Type::dict(Type::string())];

        yield [new ObjectNode(Type::object(self::class), []), Type::object(self::class)];

        yield [new CompositeNode([new ScalarNode(Type::int()), new ScalarNode(Type::string())]), Type::union(Type::int(), Type::string())];
        yield [
            new ObjectNode(Type::object(DummyWithUnionProperties::class), [
                'value' => [
                    'name' => 'value',
                    'value' => new CompositeNode([
                        new BackedEnumNode(Type::enum(DummyBackedEnum::class)),
                        new ScalarNode(Type::null()),
                        new ScalarNode(Type::string()),
                    ]),
                    'accessor' => fn () => false,
                ],
            ]),
            Type::object(DummyWithUnionProperties::class),
        ];
    }

    public function testDoNotSupportIntersectionType()
    {
        $this->expectException(UnsupportedException::class);
        $this->expectExceptionMessage('"bool&int" type is not supported.');

        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader());
        $dataModelBuilder->build(Type::intersection(Type::int(), Type::bool()), []);
    }

    public function testDoNotSupportEnumType()
    {
        $this->expectException(UnsupportedException::class);
        $this->expectExceptionMessage(\sprintf('"%s" type is not supported.', DummyEnum::class));

        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader());
        $dataModelBuilder->build(Type::enum(DummyEnum::class), []);
    }

    public function testAddGhostLeafWhenClassAlreadyGenerated()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::object(self::class), []),
        ]));

        $this->assertEquals(new ObjectNode(Type::object(self::class), [[
            'name' => 'foo',
            'value' => new ObjectNode(Type::object(self::class), [], true),
            'accessor' => fn () => false,
        ]]), $dataModelBuilder->build(Type::object(self::class), []));
    }

    public function testCallPropertyMetadataLoaderWithProperContext()
    {
        $type = Type::object(self::class);

        $propertyMetadataLoader = $this->createMock(PropertyMetadataLoaderInterface::class);
        $propertyMetadataLoader->expects($this->once())
            ->method('load')
            ->with(self::class, [], [
                'original_type' => $type,
                'generated_classes' => [(string) $type => true],
            ])
            ->willReturn([]);

        $dataModelBuilder = new DataModelBuilder($propertyMetadataLoader);
        $dataModelBuilder->build($type, []);
    }

    public function testPropertyWithoutDenormalizer()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::object(self::class), []),
        ]));

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::object(self::class), []);
        $accessor = $dataModel->getProperties()[0]['accessor'];

        $this->assertEquals(new VariableDataAccessor('data'), $accessor(new VariableDataAccessor('data')));
    }

    public function testPropertyWithDenormalizer()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata(
                'foo',
                Type::object(DummyWithNormalizerAttributes::class),
                [],
                ['denormalizer_id'],
            ),
        ]));

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::object(self::class), []);
        $accessor = $dataModel->getProperties()[0]['accessor'];

        $this->assertEquals(
            new FunctionDataAccessor(
                'denormalize',
                [new VariableDataAccessor('data'), new VariableDataAccessor('config')],
                new FunctionDataAccessor('get', [new ScalarDataAccessor('denormalizer_id')], new VariableDataAccessor('denormalizers')),
            ),
            $accessor(new VariableDataAccessor('data')),
        );
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

            public function load(string $className, array $config, array $context): array
            {
                return $this->propertiesMetadata;
            }
        };
    }
}
