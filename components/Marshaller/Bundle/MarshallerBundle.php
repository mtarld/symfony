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
use Symfony\Component\Marshaller\Hook\NativeContextBuilder\PhpstanHookNativeContextBuilder;
use Symfony\Component\Marshaller\Marshaller;
use Symfony\Component\Marshaller\MarshallerInterface;
use Symfony\Component\Marshaller\Type\PhpstanTypeExtractor;

final class MarshallerBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->setParameter('marshaller.cache_dir', sprintf('%s/marshaller', $container->getParameter('kernel.cache_dir')));

        $container->setParameter('marshaller.marshallable_paths', [sprintf('src/Dto', $container->getParameter('kernel.project_dir'))]);

        // Marshaller
        $container->register('marshaller', Marshaller::class)
            ->setArguments([
                new TaggedIteratorArgument('marshaller.context.native_context_builder'),
                new Parameter('marshaller.cache_dir'),
            ]);

        $container->setAlias(MarshallerInterface::class, 'marshaller');

        // Context
        $container->register('marshaller.context.default_factory', DefaultContextFactory::class);

        // Hook native context builders
        $container->register('marshaller.hook.native_context_builder.phpstan', PhpstanHookNativeContextBuilder::class)
            ->setArguments([
                new Reference('marshaller.type_extractor.phpstan'),
            ])
            ->addTag('marshaller.context.native_context_builder');

        // Type extractors
        $container->register('marshaller.type_extractor.phpstan', PhpstanTypeExtractor::class);

        // Cache
        $container->register('marshaller.cache.warmable_resolver', WarmableResolver::class)
            ->setArguments([
                new Parameter('marshaller.marshallable_paths'),
            ]);

        $container->register('marshaller.cache.template_warmer', TemplateCacheWarmer::class)
            ->setArguments([
                new Reference('marshaller.cache.warmable_resolver'),
                new Reference('marshaller'),
                new Reference('filesystem'),
                new Parameter('marshaller.cache_dir'),
            ])
            ->addTag('kernel.cache_warmer');
    }
}
