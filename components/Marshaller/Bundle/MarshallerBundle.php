<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Bundle;

use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Marshaller\Cache\TemplateCacheWarmer;
use Symfony\Component\Marshaller\Cache\WarmableResolver;
use Symfony\Component\Marshaller\Context\DefaultContextFactory;
use Symfony\Component\Marshaller\Hook\NativeContextBuilder\ArrayHookNativeContextBuilder;
use Symfony\Component\Marshaller\Hook\NativeContextBuilder\ObjectHookNativeContextBuilder;
use Symfony\Component\Marshaller\Hook\NativeContextBuilder\PropertyFormatterHookNativeContextBuilder;
use Symfony\Component\Marshaller\Hook\NativeContextBuilder\PropertyNameHookNativeContextBuilder;
use Symfony\Component\Marshaller\Marshaller;
use Symfony\Component\Marshaller\MarshallerInterface;
use Symfony\Component\Marshaller\Type\PhpDocTypeExtractor;
use Symfony\Component\Marshaller\Type\PhpstanTypeExtractor;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;
use Symfony\Component\Marshaller\Type\TypeExtractor;

final class MarshallerBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->setParameter('marshaller.cache_dir', sprintf('%s/marshaller', $container->getParameter('kernel.cache_dir')));
        $container->setParameter('marshaller.depth.max_depth', 8);
        $container->setParameter('marshaller.depth.reject_circular_reference', true);

        $container->setParameter('marshaller.marshallable_paths', [sprintf('src/Dto', $container->getParameter('kernel.project_dir'))]);

        // Marshaller
        $container->register('marshaller', Marshaller::class)
            ->setArguments([
                new Parameter('marshaller.cache_dir'),
                new Reference('marshaller.context.default_factory'),
                new TaggedIteratorArgument('marshaller.context.native_context_builder.marshal'),
                new TaggedIteratorArgument('marshaller.context.native_context_builder.template_generation'),
            ]);

        $container->setAlias(MarshallerInterface::class, 'marshaller');

        // Hook native context builders
        $container->register('marshaller.hook.native_context_builder.property_name', PropertyNameHookNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder.template_generation');

        $container->register('marshaller.hook.native_context_builder.property_formatter', PropertyFormatterHookNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder.marshal')
            ->addTag('marshaller.context.native_context_builder.template_generation');

        $container->register('marshaller.hook.native_context_builder.array', ArrayHookNativeContextBuilder::class)
            ->setArguments([
                new Reference('marshaller.type_extractor'),
            ])
            ->addTag('marshaller.context.native_context_builder.template_generation');

        $container->register('marshaller.hook.native_context_builder.object', ObjectHookNativeContextBuilder::class)
            ->setArguments([
                new Reference('marshaller.type_extractor'),
            ])
            ->addTag('marshaller.context.native_context_builder.template_generation');

        // Context
        $container->register('marshaller.context.default_factory', DefaultContextFactory::class)
            ->setArguments([
                new Parameter('marshaller.depth.max_depth'),
                new Parameter('marshaller.depth.reject_circular_reference'),
            ]);

        // Type extractors
        $container->register('marshaller.type_extractor.reflection', ReflectionTypeExtractor::class);
        $container->register('marshaller.type_extractor.php_doc', PhpDocTypeExtractor::class);
        $container->register('marshaller.type_extractor.phpstan', PhpstanTypeExtractor::class);

        $container->register('marshaller.type_extractor', TypeExtractor::class)
            ->setArguments([
                new Reference('marshaller.type_extractor.phpstan'),
                new Reference('marshaller.type_extractor.php_doc'),
                new Reference('marshaller.type_extractor.reflection'),
            ]);

        // // Cache
        // $container->register('marshaller.cache.warmable_resolver', WarmableResolver::class)
        //     ->setArguments([
        //         new Parameter('marshaller.marshallable_paths'),
        //     ]);
        //
        // $container->register('marshaller.cache.template_warmer', TemplateCacheWarmer::class)
        //     ->setArguments([
        //         new Reference('marshaller.cache.warmable_resolver'),
        //         new Reference('marshaller.template.loader'),
        //         new Reference('marshaller.context.declination_resolver'),
        //     ])
        //     ->addTag('kernel.cache_warmer');
    }
}
