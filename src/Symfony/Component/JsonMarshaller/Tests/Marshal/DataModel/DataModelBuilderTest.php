<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Tests\Marshal\DataModel;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\JsonMarshaller\Exception\LogicException;
use Symfony\Component\JsonMarshaller\Exception\MaxDepthException;
use Symfony\Component\JsonMarshaller\Marshal\DataModel\CollectionNode;
use Symfony\Component\JsonMarshaller\Marshal\DataModel\DataModelBuilder;
use Symfony\Component\JsonMarshaller\Marshal\DataModel\DataModelNodeInterface;
use Symfony\Component\JsonMarshaller\Marshal\DataModel\ObjectNode;
use Symfony\Component\JsonMarshaller\Marshal\DataModel\ScalarNode;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\AttributePropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\PropertyMetadata;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\TypePropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Php\ArgumentsNode;
use Symfony\Component\JsonMarshaller\Php\FunctionCallNode;
use Symfony\Component\JsonMarshaller\Php\MethodCallNode;
use Symfony\Component\JsonMarshaller\Php\PropertyNode;
use Symfony\Component\JsonMarshaller\Php\ScalarNode as PhpScalarNode;
use Symfony\Component\JsonMarshaller\Php\VariableNode;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithAttributesUsingServices;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithFormatterAttributes;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithMethods;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithNameAttributes;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithOtherDummies;
use Symfony\Component\JsonMarshaller\Type\PhpstanTypeExtractor;
use Symfony\Component\JsonMarshaller\Type\ReflectionTypeExtractor;
use Symfony\Component\JsonMarshaller\Type\Type;
use Symfony\Contracts\Service\ServiceLocatorTrait;

class DataModelBuilderTest extends TestCase
{
    /**
     * @dataProvider buildDataModelDataProvider
     */
    public function testBuildDataModel(Type $type, DataModelNodeInterface $dataModel)
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader(), null);

        $this->assertEquals($dataModel, $dataModelBuilder->build($type, new VariableNode('data'), []));
    }

    /**
     * @return iterable<array{0: Type, 1: DataModelNodeInterface}>
     */
    public static function buildDataModelDataProvider(): iterable
    {
        $accessor = new VariableNode('data');

        yield [Type::int(), new ScalarNode($accessor, Type::int())];
        yield [Type::array(), new ScalarNode($accessor, Type::array())];
        yield [Type::object(), new ScalarNode($accessor, Type::object())];
        yield [Type::class(\stdClass::class), new ScalarNode($accessor, Type::object())];
        yield [Type::union(Type::int(), Type::string()), new ScalarNode($accessor, Type::union(Type::int(), Type::string()))];
        yield [Type::intersection(Type::int(), Type::string()), new ScalarNode($accessor, Type::intersection(Type::int(), Type::string()))];

        yield [Type::list(Type::string()), new CollectionNode($accessor, Type::list(Type::string()), new ScalarNode(new VariableNode('value_0'), Type::string()))];
        yield [Type::dict(Type::string()), new CollectionNode($accessor, Type::dict(Type::string()), new ScalarNode(new VariableNode('value_0'), Type::string()))];

        yield [Type::class(self::class), new ObjectNode($accessor, Type::class(self::class), [], false)];
    }

    /**
     * @dataProvider transformedDataModelDataProvider
     */
    public function testTransformedDataModel(bool $transformed, Type $type)
    {
        $typeExtractor = new PhpstanTypeExtractor(new ReflectionTypeExtractor());
        $dataModelBuilder = new DataModelBuilder(
            new AttributePropertyMetadataLoader(new TypePropertyMetadataLoader(new PropertyMetadataLoader($typeExtractor), $typeExtractor), $typeExtractor),
            null,
        );

        $this->assertEquals(
            $transformed,
            $dataModelBuilder->build($type, new VariableNode('data'), [])->isTransformed(),
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
        yield [false, Type::class(ClassicDummy::class)];
        yield [true, Type::class(DummyWithNameAttributes::class)];
        yield [true, Type::class(DummyWithFormatterAttributes::class)];
        yield [true, Type::list(Type::class(DummyWithNameAttributes::class))];
        yield [true, Type::class(DummyWithOtherDummies::class)];
    }

    public function testThrowWhenMaxDepthIsReached()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::class(self::class), []),
        ]), null);

        $this->expectException(MaxDepthException::class);
        $dataModelBuilder->build(Type::class(self::class), new VariableNode('data'), []);
    }

    public function testCallPropertyMetadataLoaderWithProperContext()
    {
        $type = Type::class(self::class, true, [Type::int()]);

        $propertyMetadataLoader = $this->createMock(PropertyMetadataLoaderInterface::class);
        $propertyMetadataLoader->expects($this->once())
            ->method('load')
            ->with(self::class, [], [
                'original_type' => $type,
                'depth_counters' => [$type->className() => 1],
            ])
            ->willReturn([]);

        $dataModelBuilder = new DataModelBuilder($propertyMetadataLoader, null);
        $dataModelBuilder->build($type, new VariableNode('data'), []);
    }

    public function testPropertyWithSimpleAccessor()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::int(), []),
        ]), null);

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), new VariableNode('data'), []);

        $this->assertEquals(new PropertyNode(new VariableNode('data'), 'foo'), $dataModel->properties[0]->accessor);
    }

    public function testPropertyWithCustomAccessors()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::int(), ['strtoupper', DummyWithFormatterAttributes::doubleAndCastToString(...)]),
        ]), null);

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), new VariableNode('data'), []);

        $this->assertEquals(
            new FunctionCallNode(
                sprintf('%s::doubleAndCastToString', DummyWithFormatterAttributes::class),
                new ArgumentsNode([new FunctionCallNode('strtoupper', new ArgumentsNode([new PropertyNode(new VariableNode('data'), 'foo')]))]),
            ),
            $dataModel->properties[0]->accessor,
        );
    }

    public function testPropertyWithAccessorWithConfig()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata(
                'foo',
                Type::int(),
                [DummyWithFormatterAttributes::doubleAndCastToStringWithConfig(...)],
            ),
        ]), null);

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), new VariableNode('data'), []);

        $this->assertEquals(
            new FunctionCallNode(sprintf('%s::doubleAndCastToStringWithConfig', DummyWithFormatterAttributes::class), new ArgumentsNode([
                new PropertyNode(new VariableNode('data'), 'foo'),
                new VariableNode('config'),
            ])),
            $dataModel->properties[0]->accessor,
        );
    }

    public function testPropertyWithFormatterWithRuntimeServices()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata(
                'foo',
                Type::int(),
                [DummyWithAttributesUsingServices::serviceAndConfig(...)],
            ),
        ]), self::runtimeServices([
            sprintf('%s::serviceAndConfig[service]', DummyWithAttributesUsingServices::class) => 'useless',
        ]));

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), new VariableNode('data'), []);

        $this->assertEquals(
            new FunctionCallNode(sprintf('%s::serviceAndConfig', DummyWithAttributesUsingServices::class), new ArgumentsNode([
                new PropertyNode(new VariableNode('data'), 'foo'),
                new MethodCallNode(
                    new VariableNode('services'),
                    'get',
                    new ArgumentsNode([new PhpScalarNode(sprintf('%s::serviceAndConfig[service]', DummyWithAttributesUsingServices::class))]),
                ),
                new VariableNode('config'),
            ])),
            $dataModel->properties[0]->accessor,
        );
    }

    public function testPropertyWithConstAccessor()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::int(), [DummyWithMethods::const(...)]),
        ]), null);

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), new VariableNode('data'), []);

        $this->assertEquals(
            new FunctionCallNode(sprintf('%s::const', DummyWithMethods::class), new ArgumentsNode([])),
            $dataModel->properties[0]->accessor,
        );
    }

    public function testPropertyWithFormatterWithInvalidArgument()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata(
                'foo',
                Type::class(DummyWithAttributesUsingServices::class),
                [DummyWithAttributesUsingServices::serviceAndConfig(...)],
            ),
        ]), null);

        $this->expectException(LogicException::class);

        $dataModelBuilder->build(Type::class(self::class), new VariableNode('data'), []);
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
