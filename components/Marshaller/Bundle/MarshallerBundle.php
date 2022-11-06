<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Bundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Marshaller\Cache\TemplateCacheWarmer;
use Symfony\Component\Marshaller\Cache\WarmableResolver;
use Symfony\Component\Marshaller\Context\DefaultContextFactory;
use Symfony\Component\Marshaller\Hook\ArrayHookNativeContextBuilder;
use Symfony\Component\Marshaller\Hook\PropertyFormatterHookNativeContextBuilder;
use Symfony\Component\Marshaller\Hook\PropertyNameHookNativeContextBuilder;
use Symfony\Component\Marshaller\Marshaller;
use Symfony\Component\Marshaller\MarshallerInterface;

final class MarshallerBundle extends Bundle
{
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
                new Reference('marshaller.hook.native_context_builder.array'),
            ]);

        $container->setAlias(MarshallerInterface::class, 'marshaller');

        // Hook native context builders
        $container->register('marshaller.hook.native_context_builder.property_name', PropertyNameHookNativeContextBuilder::class);
        $container->register('marshaller.hook.native_context_builder.property_formatter', PropertyFormatterHookNativeContextBuilder::class);
        $container->register('marshaller.hook.native_context_builder.array', ArrayHookNativeContextBuilder::class);

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
