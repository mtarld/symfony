<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Tests\Unmarshal\DataModel;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\JsonMarshaller\Exception\LogicException;
use Symfony\Component\JsonMarshaller\Php\ArgumentsNode;
use Symfony\Component\JsonMarshaller\Php\FunctionCallNode;
use Symfony\Component\JsonMarshaller\Php\MethodCallNode;
use Symfony\Component\JsonMarshaller\Php\ScalarNode as PhpScalarNode;
use Symfony\Component\JsonMarshaller\Php\VariableNode;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithAttributesUsingServices;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithFormatterAttributes;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithMethods;
use Symfony\Component\JsonMarshaller\Type\Type;
use Symfony\Component\JsonMarshaller\Unmarshal\DataModel\CollectionNode;
use Symfony\Component\JsonMarshaller\Unmarshal\DataModel\DataModelBuilder;
use Symfony\Component\JsonMarshaller\Unmarshal\DataModel\DataModelNodeInterface;
use Symfony\Component\JsonMarshaller\Unmarshal\DataModel\ObjectNode;
use Symfony\Component\JsonMarshaller\Unmarshal\DataModel\ScalarNode;
use Symfony\Component\JsonMarshaller\Unmarshal\Mapping\PropertyMetadata;
use Symfony\Component\JsonMarshaller\Unmarshal\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Contracts\Service\ServiceLocatorTrait;

class DataModelBuilderTest extends TestCase
{
    /**
     * @dataProvider buildDataModelDataProvider
     */
    public function testBuildDataModel(Type $type, DataModelNodeInterface $dataModel)
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader(), self::runtimeServices());

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
        yield [Type::class(\stdClass::class), new ScalarNode(Type::object())];
        yield [Type::union(Type::int(), Type::string()), new ScalarNode(Type::union(Type::int(), Type::string()))];
        yield [Type::intersection(Type::int(), Type::string()), new ScalarNode(Type::intersection(Type::int(), Type::string()))];

        yield [Type::list(Type::string()), new CollectionNode(Type::list(Type::string()), new ScalarNode(Type::string()))];
        yield [Type::dict(Type::string()), new CollectionNode(Type::dict(Type::string()), new ScalarNode(Type::string()))];

        yield [Type::class(self::class), new ObjectNode(Type::class(self::class), [], false)];
    }

    public function testAddGhostLeafWhenClassAlreadyGenerated()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::class(self::class), []),
        ]), self::runtimeServices());

        $this->assertEquals(new ObjectNode(Type::class(self::class), [[
            'name' => 'foo',
            'value' => new ObjectNode(Type::class(self::class), [], true),
            'formatter' => fn () => false,
        ]], false), $dataModelBuilder->build(Type::class(self::class), []));
    }

    public function testCallPropertyMetadataLoaderWithProperContext()
    {
        $type = Type::class(self::class, true, [Type::int()]);

        $propertyMetadataLoader = $this->createMock(PropertyMetadataLoaderInterface::class);
        $propertyMetadataLoader->expects($this->once())
            ->method('load')
            ->with(self::class, [], [
                'original_type' => $type,
                'generated_classes' => [(string) $type => true],
            ])
            ->willReturn([]);

        $dataModelBuilder = new DataModelBuilder($propertyMetadataLoader, self::runtimeServices());
        $dataModelBuilder->build($type, []);
    }

    public function testPropertyWithoutFormatter()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::class(self::class), []),
        ]), self::runtimeServices());

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), []);
        $formatter = $dataModel->properties[0]['formatter'];

        $this->assertEquals(new VariableNode('data'), $formatter(new VariableNode('data')));
    }

    public function testPropertyWithSimpleFormatter()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::class(self::class), ['strtoupper', DummyWithFormatterAttributes::divideAndCastToInt(...)]),
        ]), self::runtimeServices());

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), []);
        $formatter = $dataModel->properties[0]['formatter'];

        $this->assertEquals(
            new FunctionCallNode(
                sprintf('%s::divideAndCastToInt', DummyWithFormatterAttributes::class),
                new ArgumentsNode([new FunctionCallNode('strtoupper', new ArgumentsNode([new VariableNode('data')]))]),
            ),
            $formatter(new VariableNode('data')),
        );
    }

    public function testPropertyWithFormatterWithConfig()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata(
                'foo',
                Type::class(DummyWithFormatterAttributes::class),
                [DummyWithFormatterAttributes::divideAndCastToIntWithConfig(...)],
            ),
        ]), self::runtimeServices());

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), []);
        $formatter = $dataModel->properties[0]['formatter'];

        $this->assertEquals(
            new FunctionCallNode(sprintf('%s::divideAndCastToIntWithConfig', DummyWithFormatterAttributes::class), new ArgumentsNode([
                new VariableNode('data'),
                new VariableNode('config'),
            ])),
            $formatter(new VariableNode('data')),
        );
    }

    public function testPropertyWithFormatterWithRuntimeServices()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata(
                'foo',
                Type::class(DummyWithAttributesUsingServices::class),
                [DummyWithAttributesUsingServices::serviceAndConfig(...)],
            ),
        ]), self::runtimeServices([
            sprintf('%s::serviceAndConfig[service]', DummyWithAttributesUsingServices::class) => 'useless',
        ]));

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), []);
        $formatter = $dataModel->properties[0]['formatter'];

        $this->assertEquals(
            new FunctionCallNode(sprintf('%s::serviceAndConfig', DummyWithAttributesUsingServices::class), new ArgumentsNode([
                new VariableNode('data'),
                new MethodCallNode(
                    new VariableNode('services'),
                    'get',
                    new ArgumentsNode([new PhpScalarNode(sprintf('%s::serviceAndConfig[service]', DummyWithAttributesUsingServices::class))]),
                ),
                new VariableNode('config'),
            ])),
            $formatter(new VariableNode('data')),
        );
    }

    public function testPropertyWithConstFormatter()
    {
        $dataModelBuilder = new DataModelBuilder(self::propertyMetadataLoader([
            new PropertyMetadata('foo', Type::class(self::class), [DummyWithMethods::const(...)]),
        ]), self::runtimeServices());

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), []);
        $formatter = $dataModel->properties[0]['formatter'];

        $this->assertEquals(
            new FunctionCallNode(sprintf('%s::const', DummyWithMethods::class), new ArgumentsNode([])),
            $formatter(new VariableNode('data')),
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
        ]), self::runtimeServices());

        $this->expectException(LogicException::class);

        /** @var ObjectNode $dataModel */
        $dataModel = $dataModelBuilder->build(Type::class(self::class), []);
        $dataModel->properties[0]['formatter'](new VariableNode('data'));
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
