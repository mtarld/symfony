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
use Symfony\Component\Marshaller\NativeContext\FormatterAttributeNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\HookNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\MarshalGenerateNativeContextBuilderInterface;
use Symfony\Component\Marshaller\NativeContext\NameAttributeNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\TypeFormatterNativeContextBuilder;
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
                new TaggedIteratorArgument('marshaller.context.native_context_builder.marshal_generate'),
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
        // Context builders
        //
        $container->register('marshaller.native_context_builder.hook', HookNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder.marshal_generate', ['priority' => -128])
            ->addTag('proxy', ['interface' => MarshalGenerateNativeContextBuilderInterface::class]);

        $container->register('marshaller.native_context_builder.type_formatter', TypeFormatterNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder.marshal_generate', ['priority' => -128])
            ->addTag('proxy', ['interface' => MarshalGenerateNativeContextBuilderInterface::class]);

        $container->register('marshaller.native_context_builder.name_attribute', NameAttributeNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder.marshal_generate', ['priority' => -128])
            ->addTag('proxy', ['interface' => MarshalGenerateNativeContextBuilderInterface::class]);

        $container->register('marshaller.native_context_builder.formatter_attribute', FormatterAttributeNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder.marshal_generate', ['priority' => -128])
            ->addTag('proxy', ['interface' => MarshalGenerateNativeContextBuilderInterface::class]);

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
