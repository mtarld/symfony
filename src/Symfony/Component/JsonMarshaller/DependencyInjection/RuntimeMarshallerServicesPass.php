<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\DependencyInjection;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\TypedReference;
use Symfony\Component\JsonMarshaller\Attribute\MarshalFormatter;
use Symfony\Component\JsonMarshaller\Attribute\MaxDepth;
use Symfony\Component\JsonMarshaller\Attribute\UnmarshalFormatter;
use Symfony\Component\JsonMarshaller\Exception\InvalidArgumentException;
use Symfony\Component\VarExporter\ProxyHelper;

/**
 * Creates and injects a service locator containing marshallable classes formatter's needed services.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class RuntimeMarshallerServicesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('marshaller.json.marshaller')) {
            return;
        }

        $formatters = [];
        foreach ($container->getDefinitions() as $definition) {
            if (!$definition->hasTag('marshaller.marshallable')) {
                continue;
            }

            $formatters = [
                ...$formatters,
                ...$this->formatters($container, $definition->getClass()),
            ];
        }

        $runtimeServices = [];
        foreach ($formatters as $formatter) {
            $formatterName = sprintf('%s::%s', $formatter->getClosureScopeClass()->getName(), $formatter->getName());
            foreach ($this->retrieveServices($container, $formatter) as $serviceName => $reference) {
                $runtimeServices[sprintf('%s[%s]', $formatterName, $serviceName)] = $reference;
            }
        }

        $runtimeServicesLocator = ServiceLocatorTagPass::register($container, $runtimeServices);

        $container->getDefinition('marshaller.json.marshaller')
            ->replaceArgument(2, $runtimeServicesLocator);

        $container->getDefinition('marshaller.json.unmarshaller')
            ->replaceArgument(2, $runtimeServicesLocator);

        $container->getDefinition('.marshaller.marshal.data_model_builder')
            ->replaceArgument(1, $runtimeServicesLocator);

        $container->getDefinition('.marshaller.unmarshal.data_model_builder')
            ->replaceArgument(1, $runtimeServicesLocator);
    }

    /**
     * @param class-string $className
     *
     * @return list<\ReflectionFunction>
     */
    private function formatters(ContainerBuilder $container, string $className): array
    {
        if (null === $reflection = $container->getReflectionClass($className)) {
            throw new InvalidArgumentException(sprintf('Class "%s" cannot be found.', $className));
        }

        $formatters = [];
        foreach ($reflection->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (!\in_array($attribute->getName(), [MarshalFormatter::class, UnmarshalFormatter::class, MaxDepth::class])) {
                    continue;
                }

                /** @var MarshalFormatter|UnmarshalFormatter|MaxDepth $attributeInstance */
                $attributeInstance = $attribute->newInstance();

                $formatter = $attributeInstance instanceof MarshalFormatter || $attributeInstance instanceof UnmarshalFormatter
                    ? $attributeInstance->formatter
                    : $attributeInstance->maxDepthReachedFormatter;

                $formatters[] = new \ReflectionFunction(\Closure::fromCallable($formatter));
            }
        }

        return $formatters;
    }

    /**
     * @return list<Reference>
     */
    private function retrieveServices(ContainerBuilder $container, \ReflectionFunction $function): array
    {
        $services = [];

        foreach ($function->getParameters() as $i => $parameter) {
            // first argument is always the data itself
            if (0 === $i) {
                continue;
            }

            $type = preg_replace('/(^|[(|&])\\\\/', '\1', ltrim(ProxyHelper::exportType($parameter) ?? '', '?'));

            if ($autowireAttribute = ($parameter->getAttributes(Autowire::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null)) {
                $value = $autowireAttribute->newInstance()->value;

                if ($value instanceof Reference) {
                    $services[$parameter->name] = $type
                        ? new TypedReference($value, $type, name: $parameter->name)
                        : new Reference($value);

                    continue;
                }

                $services[$parameter->name] = new Reference('.value.'.$container->hash($value));
                $container->register((string) $services[$parameter->name], 'mixed')
                    ->setFactory('current')
                    ->addArgument([$value]);

                continue;
            }

            if ('' === $type) {
                continue;
            }

            if ('array' === $type && 'config' === $parameter->name) {
                continue;
            }

            $services[$parameter->name] = new TypedReference($type, $type, name: Target::parseName($parameter));
        }

        return $services;
    }
}
