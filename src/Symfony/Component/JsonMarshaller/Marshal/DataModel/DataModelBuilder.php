<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Marshal\DataModel;

use Psr\Container\ContainerInterface;
use Symfony\Component\JsonMarshaller\Exception\LogicException;
use Symfony\Component\JsonMarshaller\Exception\MaxDepthException;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\JsonMarshaller\Marshal\VariableNameScoperTrait;
use Symfony\Component\JsonMarshaller\MarshallerInterface;
use Symfony\Component\JsonMarshaller\Php\ArgumentsNode;
use Symfony\Component\JsonMarshaller\Php\FunctionCallNode;
use Symfony\Component\JsonMarshaller\Php\MethodCallNode;
use Symfony\Component\JsonMarshaller\Php\PhpNodeInterface;
use Symfony\Component\JsonMarshaller\Php\PropertyNode;
use Symfony\Component\JsonMarshaller\Php\ScalarNode as PhpScalarNode;
use Symfony\Component\JsonMarshaller\Php\VariableNode;
use Symfony\Component\JsonMarshaller\Type\Type;
use Symfony\Component\VarExporter\ProxyHelper;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type MarshalConfig from MarshallerInterface
 */
final readonly class DataModelBuilder
{
    use VariableNameScoperTrait;

    public function __construct(
        private PropertyMetadataLoaderInterface $propertyMetadataLoader,
        private ?ContainerInterface $runtimeServices,
    ) {
    }

    /**
     * @param MarshalConfig        $config
     * @param array<string, mixed> $context
     */
    public function build(Type $type, PhpNodeInterface $accessor, array $config, array $context = []): DataModelNodeInterface
    {
        if ($type->isObject() && $type->hasClass()) {
            $className = $type->className();

            $context['depth_counters'][$className] ??= 0;
            ++$context['depth_counters'][$className];

            $maxDepth = $config['max_depth'] ?? 32;
            if ($context['depth_counters'][$className] > $maxDepth) {
                throw new MaxDepthException($className, $maxDepth);
            }

            $propertiesNodes = [];

            foreach ($this->propertyMetadataLoader->load($className, $config, ['original_type' => $type] + $context) as $marshalledName => $propertyMetadata) {
                $propertyAccessor = new PropertyNode($accessor, $propertyMetadata->name());

                foreach ($propertyMetadata->formatters() as $f) {
                    $reflection = new \ReflectionFunction(\Closure::fromCallable($f));
                    $functionName = null === $reflection->getClosureScopeClass()
                        ? $reflection->getName()
                        : sprintf('%s::%s', $reflection->getClosureScopeClass()->getName(), $reflection->getName());

                    $arguments = [];
                    foreach ($reflection->getParameters() as $i => $parameter) {
                        if (0 === $i) {
                            $arguments[] = $propertyAccessor;

                            continue;
                        }

                        $parameterType = preg_replace('/(^|[(|&])\\\\/', '\1', ltrim(ProxyHelper::exportType($parameter) ?? '', '?'));
                        if ('array' === $parameterType && 'config' === $parameter->name) {
                            $arguments[] = new VariableNode('config');

                            continue;
                        }

                        $argumentName = sprintf('%s[%s]', $functionName, $parameter->name);
                        if ($this->runtimeServices && $this->runtimeServices->has($argumentName)) {
                            $arguments[] = new MethodCallNode(new VariableNode('services'), 'get', new ArgumentsNode([new PhpScalarNode($argumentName)]));

                            continue;
                        }

                        throw new LogicException(sprintf('Cannot resolve "%s" argument of "%s()".', $parameter->name, $functionName));
                    }

                    $propertyAccessor = new FunctionCallNode($functionName, new ArgumentsNode($arguments));
                }

                $propertiesNodes[$marshalledName] = $this->build($propertyMetadata->type(), $propertyAccessor, $config, $context);
            }

            return new ObjectNode($accessor, $type, $propertiesNodes);
        }

        if ($type->isCollection() && ($type->isList() || $type->isDict())) {
            return new CollectionNode(
                $accessor,
                $type,
                $this->build($type->collectionValueType(), new VariableNode($this->scopeVariableName('value', $context)), $config, $context),
            );
        }

        return new ScalarNode($accessor, $type);
    }
}
