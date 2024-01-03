<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\DataModel\Decode;

use Psr\Container\ContainerInterface;
use Symfony\Component\JsonEncoder\DataModel\DataAccessorInterface;
use Symfony\Component\JsonEncoder\DataModel\FunctionDataAccessor;
use Symfony\Component\JsonEncoder\DataModel\ScalarDataAccessor;
use Symfony\Component\JsonEncoder\DataModel\VariableDataAccessor;
use Symfony\Component\JsonEncoder\Exception\LogicException;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadata;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\TypeInfo\Exception\LogicException as TypeInfoLogicException;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\EnumType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\VarExporter\ProxyHelper;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class DataModelBuilder
{
    public function __construct(
        private PropertyMetadataLoaderInterface $propertyMetadataLoader,
        private ?ContainerInterface $runtimeServices = null,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $context
     */
    public function build(Type $type, array $config, array $context = []): DataModelNodeInterface
    {
        $originalType = $type;
        try {
            $type = $type->isNullable() ? $type->asNonNullable() : $type;
        } catch (TypeInfoLogicException) {
        }

        if ($type instanceof EnumType) {
            return new ScalarNode($originalType);
        }

        if ($type instanceof ObjectType) {
            $transformed = false;
            $typeString = (string) $type;
            $className = $type->getClassName();

            if ($context['generated_classes'][$typeString] ??= false) {
                return ObjectNode::ghost($type);
            }

            $propertiesNodes = [];
            $context['generated_classes'][$typeString] = true;

            $propertiesMetadata = $this->propertyMetadataLoader->load($className, $config, ['original_type' => $originalType] + $context);

            if (\count((new \ReflectionClass($className))->getProperties()) !== \count($propertiesMetadata)
                || array_values(array_map(fn (PropertyMetadata $m): string => $m->name, $propertiesMetadata)) !== array_keys($propertiesMetadata)
            ) {
                $transformed = true;
            }

            foreach ($propertiesMetadata as $encodedName => $propertyMetadata) {
                $propertiesNodes[$encodedName] = [
                    'name' => $propertyMetadata->name,
                    'value' => $this->build($propertyMetadata->type, $config, $context),
                    'accessor' => function (DataAccessorInterface $accessor) use ($propertyMetadata): DataAccessorInterface {
                        foreach ($propertyMetadata->formatters as $f) {
                            $reflection = new \ReflectionFunction(\Closure::fromCallable($f));
                            $functionName = null === $reflection->getClosureScopeClass()
                                ? $reflection->getName()
                                : sprintf('%s::%s', $reflection->getClosureScopeClass()->getName(), $reflection->getName());

                            $arguments = [];
                            foreach ($reflection->getParameters() as $i => $parameter) {
                                if (0 === $i) {
                                    $arguments[] = $accessor;

                                    continue;
                                }

                                $parameterType = preg_replace('/^\\\\/', '\1', ltrim(ProxyHelper::exportType($parameter) ?? '', '?'));
                                if ('array' === $parameterType && 'config' === $parameter->name) {
                                    $arguments[] = new VariableDataAccessor('config');

                                    continue;
                                }

                                $argumentName = sprintf('%s[%s]', $functionName, $parameter->name);
                                if ($this->runtimeServices && $this->runtimeServices->has($argumentName)) {
                                    $arguments[] = new FunctionDataAccessor(
                                        'get',
                                        [new ScalarDataAccessor($argumentName)],
                                        new VariableDataAccessor('services'),
                                    );

                                    continue;
                                }

                                throw new LogicException(sprintf('Cannot resolve "%s" argument of "%s()".', $parameter->name, $functionName));
                            }

                            $accessor = new FunctionDataAccessor($functionName, $arguments);
                        }

                        return $accessor;
                    },
                ];

                $transformed = $transformed || $propertiesNodes[$encodedName]['value']->isTransformed() || \count($propertyMetadata->formatters);
            }

            return new ObjectNode($originalType, $propertiesNodes, $transformed);
        }

        if ($type instanceof CollectionType) {
            return new CollectionNode($originalType, $this->build($type->getCollectionValueType(), $config, $context));
        }

        return new ScalarNode($originalType);
    }
}
