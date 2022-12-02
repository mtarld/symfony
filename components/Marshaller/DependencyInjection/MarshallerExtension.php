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
use Symfony\Component\Marshaller\Marshaller;
use Symfony\Component\Marshaller\MarshallerInterface;
use Symfony\Component\Marshaller\NativeContext\Generation\FormatterAttributeNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\Generation\HookNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\Generation\NameAttributeNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\Generation\TypeFormatterNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\GenerationNativeContextBuilderInterface;
use Symfony\Component\Marshaller\NativeContext\Marshal\JsonEncodeFlagsNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\Marshal\TypeNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\MarshalNativeContextBuilderInterface;
use Symfony\Component\Marshaller\Type\PhpstanTypeExtractor;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;
use Symfony\Component\Marshaller\Type\TypeExtractorInterface;

final class MarshallerExtension extends Extension
{
    /**
     * @param array<string, mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        //
        // Marshaller
        //
        $container->register('marshaller', Marshaller::class)
            ->setArguments([
                new Reference('marshaller.type_extractor'),
                new TaggedIteratorArgument('marshaller.context.native_context_builder.marshal'),
                new TaggedIteratorArgument('marshaller.context.native_context_builder.generation'),
                new Parameter('marshaller.cache_dir'),
            ]);

        $container->setAlias(MarshallerInterface::class, 'marshaller');

        //
        // Type extractors
        //
        $container
            ->register('marshaller.type_extractor.reflection', ReflectionTypeExtractor::class)
            ->setLazy(true)
            ->addTag('proxy', ['interface' => TypeExtractorInterface::class]);

        $container->setAlias('marshaller.type_extractor', 'marshaller.type_extractor.reflection');

        $container->register('marshaller.type_extractor.phpstan', PhpstanTypeExtractor::class)
            ->setDecoratedService('marshaller.type_extractor')
            ->setArguments([
                new Reference('.inner'),
            ])
            ->addTag('proxy', ['interface' => TypeExtractorInterface::class]);

        //
        // Generation context builders
        //
        $container->register('marshaller.native_context_builder.generation.hook', HookNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder.generation', ['priority' => -128])
            ->addTag('proxy', ['interface' => GenerationNativeContextBuilderInterface::class]);

        $container->register('marshaller.native_context_builder.generation.type_formatter', TypeFormatterNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder.generation', ['priority' => -128])
            ->addTag('proxy', ['interface' => GenerationNativeContextBuilderInterface::class]);

        $container->register('marshaller.native_context_builder.generation.name_attribute', NameAttributeNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder.generation', ['priority' => -128])
            ->addTag('proxy', ['interface' => GenerationNativeContextBuilderInterface::class]);

        $container->register('marshaller.native_context_builder.generation.formatter_attribute', FormatterAttributeNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder.generation', ['priority' => -128])
            ->addTag('proxy', ['interface' => GenerationNativeContextBuilderInterface::class]);

        //
        // Marshal context builders
        //
        $container->register('marshaller.native_context_builder.marshal.type', TypeNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder.marshal', ['priority' => -128])
            ->addTag('proxy', ['interface' => MarshalNativeContextBuilderInterface::class]);

        $container->register('marshaller.native_context_builder.marshal.json_encode_flags', JsonEncodeFlagsNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder.marshal', ['priority' => -128])
            ->addTag('proxy', ['interface' => MarshalNativeContextBuilderInterface::class]);

        //
        // Cache
        //
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
