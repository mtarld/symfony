<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\DataModel\Encode;

use Symfony\Component\JsonEncoder\DataModel\DataAccessorInterface;
use Symfony\Component\JsonEncoder\DataModel\FunctionDataAccessor;
use Symfony\Component\JsonEncoder\DataModel\PropertyDataAccessor;
use Symfony\Component\JsonEncoder\DataModel\ScalarDataAccessor;
use Symfony\Component\JsonEncoder\DataModel\VariableDataAccessor;
use Symfony\Component\JsonEncoder\Exception\LogicException;
use Symfony\Component\JsonEncoder\Exception\MaxDepthException;
use Symfony\Component\JsonEncoder\Exception\UnsupportedException;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadata;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\JsonEncoder\VariableNameScoperTrait;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\EnumType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\VarExporter\ProxyHelper;

/**
 * Builds a encoding graph representation of a given type.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class DataModelBuilder
{
    use VariableNameScoperTrait;

    public function __construct(
        private PropertyMetadataLoaderInterface $propertyMetadataLoader,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $context
     */
    public function build(Type $type, DataAccessorInterface $accessor, array $config, array $context = []): DataModelNodeInterface
    {
        $context['original_type'] ??= $type;

        if ($type instanceof UnionType) {
            return new CompositeNode($accessor, array_map(fn (Type $t): DataModelNodeInterface => $this->build($t, $accessor, $config, $context), $type->getTypes()));
        }

        if ($type instanceof BuiltinType || $type instanceof BackedEnumType) {
            return new ScalarNode($accessor, $type);
        }

        if ($type instanceof ObjectType && !$type instanceof EnumType) {
            $transformed = false;
            $className = $type->getClassName();

            $context['depth_counters'][$className] ??= 0;
            ++$context['depth_counters'][$className];

            $maxDepth = $config['max_depth'] ?? 32;
            if ($context['depth_counters'][$className] > $maxDepth) {
                throw new MaxDepthException($className, $maxDepth);
            }

            $propertiesMetadata = $this->propertyMetadataLoader->load($className, $config, ['original_type' => $type] + $context);

            if (\count((new \ReflectionClass($className))->getProperties()) !== \count($propertiesMetadata)
                || array_values(array_map(fn (PropertyMetadata $m): string => $m->getName(), $propertiesMetadata)) !== array_keys($propertiesMetadata)
            ) {
                $transformed = true;
            }

            $propertiesNodes = [];

            foreach ($propertiesMetadata as $encodedName => $propertyMetadata) {
                $propertyAccessor = new PropertyDataAccessor($accessor, $propertyMetadata->getName());

                foreach ($propertyMetadata->getNormalizers() as $normalizerId) {
                    $transformed = true;
                    $normalizerServiceAccessor = new FunctionDataAccessor('get', [new ScalarDataAccessor($normalizerId)], new VariableDataAccessor('normalizers'));
                    $propertyAccessor = new FunctionDataAccessor('normalize', [$propertyAccessor, new VariableDataAccessor('config')], $normalizerServiceAccessor);
                }

                $propertiesNodes[$encodedName] = $this->build($propertyMetadata->getType(), $propertyAccessor, $config, $context);
            }

            return new ObjectNode($accessor, $type, $propertiesNodes, $transformed);
        }

        if ($type instanceof CollectionType) {
            return new CollectionNode(
                $accessor,
                $type,
                $this->build($type->getCollectionValueType(), new VariableDataAccessor($this->scopeVariableName('value', $context)), $config, $context),
            );
        }

        throw new UnsupportedException(\sprintf('"%s" type is not supported.', (string) $type));
    }
}
