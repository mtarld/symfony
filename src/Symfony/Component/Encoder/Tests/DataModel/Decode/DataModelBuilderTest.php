<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\Tests\DataModel\Decode;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Encoder\DataModel\Decode\CollectionNode;
use Symfony\Component\Encoder\DataModel\Decode\DataModelBuilder;
use Symfony\Component\Encoder\DataModel\Decode\DataModelNodeInterface;
use Symfony\Component\Encoder\DataModel\Decode\ObjectNode;
use Symfony\Component\Encoder\DataModel\Decode\ScalarNode;
use Symfony\Component\Encoder\DataModel\FunctionDataAccessor;
use Symfony\Component\Encoder\DataModel\ScalarDataAccessor;
use Symfony\Component\Encoder\DataModel\VariableDataAccessor;
use Symfony\Component\Encoder\Exception\LogicException;
use Symfony\Component\Encoder\Mapping\Decode\AttributePropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\PropertyMetadata;
use Symfony\Component\Encoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\Encoder\Tests\Fixtures\Model\ClassicDummy;
use Symfony\Component\Encoder\Tests\Fixtures\Model\DummyWithAttributesUsingServices;
use Symfony\Component\Encoder\Tests\Fixtures\Model\DummyWithFormatterAttributes;
use Symfony\Component\Encoder\Tests\Fixtures\Model\DummyWithMethods;
use Symfony\Component\Encoder\Tests\Fixtures\Model\DummyWithNameAttributes;
use Symfony\Component\Encoder\Tests\Fixtures\Model\DummyWithOtherDummies;
use Symfony\Component\Encoder\Tests\TypeResolverAwareTrait;
use Symfony\Component\TypeInfo\Type;
use Symfony\Contracts\Service\ServiceLocatorTrait;

class DataModelBuilderTest extends TestCase
{
    use TypeResolverAwareTrait;

    /**
     * @dataProvider buildDataModelDataProvider
     */
    public function testBuildDataModel(Type $type, DataModelNodeInterface $dataModel)
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader());

        $this->assertEquals($dataModel, $dataModelBuilder->build($type, []));
    }

    /**
     * @return iterable<array{0: Type, 1: DataModelNodeInterface}>
     */
    public static function buildDataModelDataProvider(): iterable
    {
        yield [Type::int(), new ScalarNode(Type::int())];
        yield [Type::array(), new ScalarNode(Type::array())];
        yield [Type::object(), new ScalarNode(Type::object())];
        yield [Type::object(\stdClass::class), new ScalarNode(Type::object())];
        yield [Type::union(Type::int(), Type::string()), new ScalarNode(Type::union(Type::int(), Type::string()))];
        yield [Type::intersection(Type::int(), Type::string()), new ScalarNode(Type::intersection(Type::int(), Type::string()))];

        yield [Type::list(Type::string()), new CollectionNode(Type::list(Type::string()), new ScalarNode(Type::string()))];
        yield [Type::dict(Type::string()), new CollectionNode(Type::dict(Type::string()), new ScalarNode(Type::string()))];

        yield [Type::object(self::class), new ObjectNode(Type::object(self::class), [], true)];
    }

    /**
     * @dataProvider transformedDataModelDataProvider
     */
    public function testTransformedDataModel(bool $transformed, Type $type)
    {
        $typeResolver = self::getTypeResolver();
        $dataModelBuilder = new DataModelBuilder(new AttributePropertyMetadataLoader(new PropertyMetadataLoader($typeResolver), $typeResolver));

        $this->assertEquals(
            $transformed,
            $dataModelBuilder->build($type, [])->isTransformed(),
        );
    }

    /**
     * @return iterable<array{0: bool, 1: Type}>
     */
    public static function transformedDataModelDataProvider(): iterable
    {
        yield [false, Type::int()];
        yield [false, Type::object()];
        yield [false, Type::list(Type::int())];
        yield [false, Type::iterableList(Type::int())];
        yield [false, Type::object(ClassicDummy::class)];
        yield [true, Type::object(DummyWithNameAttributes::class)];
        yield [true, Type::object(DummyWithFormatterAttributes::class)];
        yield [true, Type::list(Type::object(DummyWithNameAttributes::class))];
        yield [true, Type::object(DummyWithOtherDummies::class)];
    }

    public function testAddGhostLeafWhenClassAlreadyGenerated()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::object(self::class), []),
        ]));

        $this->assertEquals(new ObjectNode(Type::object(self::class), [[
            'name' => 'foo',
            'value' => new ObjectNode(Type::object(self::class), [], false, true),
            'accessor' => fn () => false,
        ]], true), $dataModelBuilder->build(Type::object(self::class), []));
    }

    public function testCallPropertyMetadataLoaderWithProperContext()
    {
        $type = Type::object(self::class, true, [Type::int()]);

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

    public function testPropertyWithoutFormatter()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::object(self::class), []),
        ]));

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::object(self::class), []);
        $accessor = $dataModel->properties[0]['accessor'];

        $this->assertEquals(new VariableDataAccessor('data'), $accessor(new VariableDataAccessor('data')));
    }

    public function testPropertyWithSimpleFormatter()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::object(self::class), ['strtoupper', DummyWithFormatterAttributes::divideAndCastToInt(...)]),
        ]));

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::object(self::class), []);
        $accessor = $dataModel->properties[0]['accessor'];

        $this->assertEquals(
            new FunctionDataAccessor(
                sprintf('%s::divideAndCastToInt', DummyWithFormatterAttributes::class),
                [new FunctionDataAccessor('strtoupper', [new VariableDataAccessor('data')])],
            ),
            $accessor(new VariableDataAccessor('data')),
        );
    }

    public function testPropertyWithFormatterWithConfig()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata(
                'foo',
                Type::object(DummyWithFormatterAttributes::class),
                [DummyWithFormatterAttributes::divideAndCastToIntWithConfig(...)],
            ),
        ]));

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::object(self::class), []);
        $accessor = $dataModel->properties[0]['accessor'];

        $this->assertEquals(
            new FunctionDataAccessor(sprintf('%s::divideAndCastToIntWithConfig', DummyWithFormatterAttributes::class), [
                new VariableDataAccessor('data'),
                new VariableDataAccessor('config'),
            ]),
            $accessor(new VariableDataAccessor('data')),
        );
    }

    public function testPropertyWithFormatterWithRuntimeServices()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata(
                'foo',
                Type::object(DummyWithAttributesUsingServices::class),
                [DummyWithAttributesUsingServices::serviceAndConfig(...)],
            ),
        ]), self::runtimeServices([
            sprintf('%s::serviceAndConfig[service]', DummyWithAttributesUsingServices::class) => 'useless',
        ]));

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::object(self::class), []);
        $accessor = $dataModel->properties[0]['accessor'];

        $this->assertEquals(
            new FunctionDataAccessor(sprintf('%s::serviceAndConfig', DummyWithAttributesUsingServices::class), [
                new VariableDataAccessor('data'),
                new FunctionDataAccessor(
                    'get',
                    [new ScalarDataAccessor(sprintf('%s::serviceAndConfig[service]', DummyWithAttributesUsingServices::class))],
                    new VariableDataAccessor('services'),
                ),
                new VariableDataAccessor('config'),
            ]),
            $accessor(new VariableDataAccessor('data')),
        );
    }

    public function testPropertyWithConstFormatter()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::object(self::class), [DummyWithMethods::const(...)]),
        ]));

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::object(self::class), []);
        $accessor = $dataModel->properties[0]['accessor'];

        $this->assertEquals(
            new FunctionDataAccessor(sprintf('%s::const', DummyWithMethods::class), []),
            $accessor(new VariableDataAccessor('data')),
        );
    }

    public function testPropertyWithFormatterWithInvalidArgument()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata(
                'foo',
                Type::object(DummyWithAttributesUsingServices::class),
                [DummyWithAttributesUsingServices::serviceAndConfig(...)],
            ),
        ]));

        $this->expectException(LogicException::class);

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::object(self::class), []);
        $dataModel->properties[0]['accessor'](new VariableDataAccessor('data'));
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

    /**
     * @param array<string, mixed> $runtimeServices
     */
    private static function runtimeServices(array $runtimeServices = []): ContainerInterface
    {
        return new class($runtimeServices) implements ContainerInterface {
            use ServiceLocatorTrait;
        };
    }
}
