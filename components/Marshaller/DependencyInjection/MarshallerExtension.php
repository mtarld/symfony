<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\DependencyInjection;

use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Marshaller\Cache\TemplateCacheWarmer;
use Symfony\Component\Marshaller\Cache\WarmableResolver;
use Symfony\Component\Marshaller\Context\NativeContextBuilder\CacheDirNativeContextBuilder;
use Symfony\Component\Marshaller\Context\NativeContextBuilder\HookNativeContextBuilder;
use Symfony\Component\Marshaller\Context\NativeContextBuilder\NullableDataNativeContextBuilder;
use Symfony\Component\Marshaller\Context\NativeContextBuilder\TypeNativeContextBuilder;
use Symfony\Component\Marshaller\Context\NativeContextBuilder\ValidateDataNativeContextBuilder;
use Symfony\Component\Marshaller\Hook\PhpstanType\PhpstanTypeHookNativeContextBuilder;
use Symfony\Component\Marshaller\Marshaller;
use Symfony\Component\Marshaller\MarshallerInterface;

final class MarshallerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Marshaller
        $container->register('marshaller', Marshaller::class)
            ->setArguments([
                new TaggedIteratorArgument('marshaller.context.native_context_builder'),
            ]);

        $container->setAlias(MarshallerInterface::class, 'marshaller');

        // Context builder
        $container->register('marshaller.native_context_builder.cache_path', CacheDirNativeContextBuilder::class)
            ->setArguments([
                new Parameter('marshaller.cache_dir'),
            ])
            ->addTag('marshaller.context.native_context_builder', ['priority' => 1000]);

        $container->register('marshaller.native_context_builder.validate_data', ValidateDataNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder', ['priority' => 900]);

        $container->register('marshaller.native_context_builder.nullable_data', NullableDataNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder', ['priority' => 800]);

        $container->register('marshaller.native_context_builder.type', TypeNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder', ['priority' => 700]);

        $container->register('marshaller.native_context_builder.phpstan_type.hook', PhpstanTypeHookNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder', ['priority' => 600]);

        $container->register('marshaller.native_context_builder.hook', HookNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder', ['priority' => 500]);

        // Cache
        $container->register('marshaller.cache.warmable_resolver', WarmableResolver::class)
            ->setArguments([
                new Parameter('marshaller.warmable_paths'),
            ]);

        $container->register('marshaller.cache.template_warmer', TemplateCacheWarmer::class)
            ->setArguments([
                new Reference('marshaller.cache.warmable_resolver'),
                new Reference('marshaller'),
                new Parameter('marshaller.cache_dir'),
                new Parameter('marshaller.warmable_formats'),
                new Parameter('marshaller.warmable_nullable_data'),
            ])
            ->addTag('kernel.cache_warmer');
    }
}
