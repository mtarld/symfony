<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\DataModel\Decode;

use Psr\Container\ContainerInterface;
use Symfony\Component\Encoder\DataModel\DataAccessorInterface;
use Symfony\Component\Encoder\DataModel\FunctionDataAccessor;
use Symfony\Component\Encoder\DataModel\ScalarDataAccessor;
use Symfony\Component\Encoder\DataModel\VariableDataAccessor;
use Symfony\Component\Encoder\DecoderInterface;
use Symfony\Component\Encoder\Exception\LogicException;
use Symfony\Component\Encoder\Mapping\PropertyMetadata;
use Symfony\Component\Encoder\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\VarExporter\ProxyHelper;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type DecodeConfig from DecoderInterface
 */
final readonly class DataModelBuilder
{
    public function __construct(
        private PropertyMetadataLoaderInterface $propertyMetadataLoader,
        private ?ContainerInterface $runtimeServices = null,
    ) {
    }

    /**
     * @param DecodeConfig         $config
     * @param array<string, mixed> $context
     */
    public function build(Type $type, array $config, array $context = []): DataModelNodeInterface
    {
        if ($type->isObject() && !$type->isEnum() && \stdClass::class !== $type->getClassName()) {
            $transformed = false;
            $typeString = (string) $type;

            if ($context['generated_classes'][$typeString] ??= false) {
                return ObjectNode::ghost($type);
            }

            $propertiesNodes = [];
            $context['generated_classes'][$typeString] = true;

            $propertiesMetadata = $this->propertyMetadataLoader->load($type->getClassName(), $config, ['original_type' => $type] + $context);

            if (array_values(array_map(fn (PropertyMetadata $m): string => $m->getName(), $propertiesMetadata)) !== array_keys($propertiesMetadata)) {
                $transformed = true;
            }

            foreach ($propertiesMetadata as $encodedName => $propertyMetadata) {
                $propertiesNodes[$encodedName] = [
                    'name' => $propertyMetadata->getName(),
                    'value' => $this->build($propertyMetadata->getType(), $config, $context),
                    'accessor' => function (DataAccessorInterface $accessor) use ($propertyMetadata): DataAccessorInterface {
                        foreach ($propertyMetadata->getFormatters() as $f) {
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

                $transformed = $transformed || $propertiesNodes[$encodedName]['value']->isTransformed() || \count($propertyMetadata->getFormatters());
            }

            return new ObjectNode($type, $propertiesNodes, $transformed);
        }

        if ($type->isCollection() && ($type->isList() || $type->isDict())) {
            return new CollectionNode($type, $this->build($type->getCollectionValueType(), $config, $context));
        }

        return new ScalarNode($type);
    }
}
