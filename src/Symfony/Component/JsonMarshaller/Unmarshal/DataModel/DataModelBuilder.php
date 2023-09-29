<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Unmarshal\DataModel;

use Psr\Container\ContainerInterface;
use Symfony\Component\JsonMarshaller\Exception\LogicException;
use Symfony\Component\JsonMarshaller\Php\ArgumentsNode;
use Symfony\Component\JsonMarshaller\Php\FunctionCallNode;
use Symfony\Component\JsonMarshaller\Php\MethodCallNode;
use Symfony\Component\JsonMarshaller\Php\PhpNodeInterface;
use Symfony\Component\JsonMarshaller\Php\ScalarNode as PhpScalarNode;
use Symfony\Component\JsonMarshaller\Php\VariableNode;
use Symfony\Component\JsonMarshaller\Type\Type;
use Symfony\Component\JsonMarshaller\Unmarshal\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\JsonMarshaller\UnmarshallerInterface;
use Symfony\Component\VarExporter\ProxyHelper;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type UnmarshalConfig from UnmarshallerInterface
 */
final readonly class DataModelBuilder
{
    public function __construct(
        private PropertyMetadataLoaderInterface $propertyMetadataLoader,
        private ?ContainerInterface $runtimeServices,
    ) {
    }

    /**
     * @param UnmarshalConfig      $config
     * @param array<string, mixed> $context
     */
    public function build(Type $type, array $config, array $context = []): DataModelNodeInterface
    {
        if ($type->isObject() && $type->hasClass()) {
            $typeString = (string) $type;

            if ($context['generated_classes'][$typeString] ??= false) {
                return ObjectNode::ghost($type);
            }

            $propertiesNodes = [];
            $context['generated_classes'][$typeString] = true;

            foreach ($this->propertyMetadataLoader->load($type->className(), $config, ['original_type' => $type] + $context) as $marshalledName => $propertyMetadata) {
                $propertiesNodes[$marshalledName] = [
                    'name' => $propertyMetadata->name(),
                    'value' => $this->build($propertyMetadata->type(), $config, $context),
                    'formatter' => function (PhpNodeInterface $provider) use ($propertyMetadata): PhpNodeInterface {
                        $formatter = $provider;

                        foreach ($propertyMetadata->formatters() as $f) {
                            $reflection = new \ReflectionFunction(\Closure::fromCallable($f));
                            $functionName = null === $reflection->getClosureScopeClass()
                                ? $reflection->getName()
                                : sprintf('%s::%s', $reflection->getClosureScopeClass()->getName(), $reflection->getName());

                            $arguments = [];
                            foreach ($reflection->getParameters() as $i => $parameter) {
                                if (0 === $i) {
                                    $arguments[] = $formatter;

                                    continue;
                                }

                                $parameterType = preg_replace('/^\\\\/', '\1', ltrim(ProxyHelper::exportType($parameter) ?? '', '?'));
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

                            $formatter = new FunctionCallNode($functionName, new ArgumentsNode($arguments));
                        }

                        return $formatter;
                    },
                ];
            }

            return new ObjectNode($type, $propertiesNodes);
        }

        if ($type->isCollection() && ($type->isList() || $type->isDict())) {
            return new CollectionNode($type, $this->build($type->collectionValueType(), $config, $context));
        }

        return new ScalarNode($type);
    }
}
