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
use Symfony\Component\Marshaller\Context\NativeContextBuilder\CacheDirNativeContextBuilder;
use Symfony\Component\Marshaller\Context\NativeContextBuilder\HookNativeContextBuilder;
use Symfony\Component\Marshaller\Context\NativeContextBuilder\NullableDataNativeContextBuilder;
use Symfony\Component\Marshaller\Context\NativeContextBuilder\PhpstanNativeContextBuilder;
use Symfony\Component\Marshaller\Context\NativeContextBuilder\TypeNativeContextBuilder;
use Symfony\Component\Marshaller\Marshaller;
use Symfony\Component\Marshaller\MarshallerInterface;
use Symfony\Component\Marshaller\Type\PhpstanTypeExtractor;

final class MarshallerBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->setParameter('marshaller.cache_dir', sprintf('%s/marshaller', $container->getParameter('kernel.cache_dir')));
        $container->setParameter('marshaller.warmable_paths', [sprintf('src/Dto', $container->getParameter('kernel.project_dir'))]);
        $container->setParameter('marshaller.warmable_formats', ['json']);
        $container->setParameter('marshaller.warmable_nullable_data', true);

        // Marshaller
        $container->register('marshaller', Marshaller::class)
            ->setArguments([
                new TaggedIteratorArgument('marshaller.context.native_context_builder.generation'),
                new TaggedIteratorArgument('marshaller.context.native_context_builder.marshal'),
                new Parameter('marshaller.cache_dir'),
            ]);

        $container->setAlias(MarshallerInterface::class, 'marshaller');

        // Context builder
        $container->register('marshaller.native_context_builder.cache_path', CacheDirNativeContextBuilder::class)
            ->setArguments([
                new Parameter('marshaller.cache_dir'),
            ])
            ->addTag('marshaller.context.native_context_builder.generation', ['priority' => 1000])
            ->addTag('marshaller.context.native_context_builder.marshal', ['priority' => 1000]);

        $container->register('marshaller.native_context_builder.nullable_data', NullableDataNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder.generation', ['priority' => 900])
            ->addTag('marshaller.context.native_context_builder.marshal', ['priority' => 900]);

        $container->register('marshaller.native_context_builder.type', TypeNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder.generation', ['priority' => 800])
            ->addTag('marshaller.context.native_context_builder.marshal', ['priority' => 800]);

        $container->register('marshaller.native_context_builder.phpstan', PhpstanNativeContextBuilder::class)
            ->setArguments([
                new Reference('marshaller.type_extractor.phpstan'),
            ])
            ->addTag('marshaller.context.native_context_builder.generation', ['priority' => 700]);

        $container->register('marshaller.native_context_builder.hook', HookNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder.generation', ['priority' => 600]);

        // Type extractors
        $container->register('marshaller.type_extractor.phpstan', PhpstanTypeExtractor::class);

        // Cache
        $container->register('marshaller.cache.warmable_resolver', WarmableResolver::class)
            ->setArguments([
                new Parameter('marshaller.warmable_paths'),
            ]);

        $container->register('marshaller.cache.template_warmer', TemplateCacheWarmer::class)
            ->setArguments([
                new Reference('marshaller.cache.warmable_resolver'),
                new Reference('marshaller'),
                new Reference('filesystem'),
                new Parameter('marshaller.cache_dir'),
                new Parameter('marshaller.warmable_formats'),
                new Parameter('marshaller.warmable_nullable_data'),
            ])
            ->addTag('kernel.cache_warmer');
    }
}
