<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Bundle;

use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Marshaller\Cache\TemplateCacheWarmer;
use Symfony\Component\Marshaller\Cache\WarmableResolver;
use Symfony\Component\Marshaller\Context\DefaultContextFactory;
use Symfony\Component\Marshaller\Hook\PropertyFormatterHookNativeContextBuilder;
use Symfony\Component\Marshaller\Hook\PropertyNameHookNativeContextBuilder;
use Symfony\Component\Marshaller\Marshaller;
use Symfony\Component\Marshaller\MarshallerInterface;
use Symfony\Component\Marshaller\Metadata\Attribute\PropertyAttributeResolver;
use Symfony\Component\Marshaller\Metadata\ClassMetadataFactory;
use Symfony\Component\Marshaller\Metadata\NameConverter\NameAttributePropertyNameConverter;
use Symfony\Component\Marshaller\Metadata\NameConverter\NoOpPropertyNameConverter;
use Symfony\Component\Marshaller\Metadata\PropertyMetadataFactory;
use Symfony\Component\Marshaller\Metadata\Type\MethodReturnTypeExtractor;
use Symfony\Component\Marshaller\Metadata\Type\PropertyTypeExtractor;
use Symfony\Component\Marshaller\Metadata\Type\TypeFactory;
use Symfony\Component\Marshaller\Metadata\ValueMetadataFactory;

final class MarshallerBundle extends Bundle
{
    // TODO see what should be internal
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->setParameter('marshaller.marshallable_paths', [
            sprintf('src/Dto', $container->getParameter('kernel.project_dir')),
        ]);

        $container->setParameter('marshaller.cache_dir', sprintf('%s/marshaller', $container->getParameter('kernel.cache_dir')));
        $container->setParameter('marshaller.depth.max_depth', 8);
        $container->setParameter('marshaller.depth.reject_circular_reference', true);

        // Marshaller
        $container->register('marshaller', Marshaller::class)
            ->setArguments([
                new Parameter('marshaller.cache_dir'),
                new Reference('marshaller.context.default_factory'),
                new Reference('marshaller.hook.native_context_builder.property_name'),
                new Reference('marshaller.hook.native_context_builder.property_formatter'),
            ]);

        $container->setAlias(MarshallerInterface::class, 'marshaller');

        // Hook native context builders
        $container->register('marshaller.hook.native_context_builder.property_name', PropertyNameHookNativeContextBuilder::class);
        $container->register('marshaller.hook.native_context_builder.property_formatter', PropertyFormatterHookNativeContextBuilder::class);

        // Name converters
        $container->register('marshaller.metadata.property_name_converter.no_op', NoOpPropertyNameConverter::class);

        $container->register('marshaller.metadata.property_name_converter.marshalled_name', NameAttributePropertyNameConverter::class)
            ->setDecoratedService('marshaller.metadata.property_name_converter.no_op')
            ->setArguments([
                new Reference('.inner'),
            ]);

        $container->setAlias('marshaller.metadata.property_name_converter', 'marshaller.metadata.property_name_converter.no_op');

        // Metadata
        $container->register('marshaller.metadata.class_factory', ClassMetadataFactory::class)
            ->setArguments([
                new Reference('marshaller.metadata.property_factory'),
                new Reference('marshaller.metadata.property_filterer'),
            ]);

        $container->register('marshaller.metadata.property_factory', PropertyMetadataFactory::class)
            ->setArguments([
                new Reference('marshaller.metadata.value_factory'),
                new Reference('marshaller.metadata.property_attribute_resolver'),
                new Reference('marshaller.metadata.property_name_converter'),
            ]);

        $container->register('marshaller.metadata.value_factory', ValueMetadataFactory::class)
            ->setArguments([
                new ServiceLocatorArgument([
                    ClassMetadataFactory::class => new Reference('marshaller.metadata.class_factory'),
                ]),
                new Reference('marshaller.metadata.property_type_extractor'),
                new Reference('marshaller.metadata.method_return_type_extractor'),
                new Parameter('marshaller.depth.max_depth'),
                new Parameter('marshaller.depth.reject_circular_reference'),
            ]);

        $container->register('marshaller.metadata.property_attribute_resolver', PropertyAttributeResolver::class);

        // Type extractors
        $container->register('marshaller.metadata.type_factory', TypeFactory::class);

        $container->register('marshaller.metadata.property_type_extractor', PropertyTypeExtractor::class)
            ->setArguments([
                new Reference('property_info'),
                new Reference('marshaller.metadata.type_factory'),
            ]);

        $container->register('marshaller.metadata.method_return_type_extractor', MethodReturnTypeExtractor::class)
            ->setArguments([
                new Reference('marshaller.metadata.type_factory'),
            ]);

        // Cache
        $container->register('marshaller.cache.warmable_resolver', WarmableResolver::class)
            ->setArguments([
                new Parameter('marshaller.marshallable_paths'),
            ]);

        // Context
        $container->register('marshaller.context.default_factory', DefaultContextFactory::class)
            ->setArguments([
                new Parameter('marshaller.depth.max_depth'),
                new Parameter('marshaller.depth.reject_circular_reference'),
            ]);

        // $container->register('marshaller.cache.template_warmer', TemplateCacheWarmer::class)
        //     ->setArguments([
        //         new Reference('marshaller.cache.warmable_resolver'),
        //         new Reference('marshaller.template.loader'),
        //         new Reference('marshaller.context.declination_resolver'),
        //     ])
        //     ->addTag('kernel.cache_warmer');
    }
}
