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
use Symfony\Component\Marshaller\NativeContext\CacheDirNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\NameAttributeNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\PropertyNameFormatterNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\PropertyValueFormatterNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\TypeExtractorNativeContextBuilder;
use Symfony\Component\Marshaller\Type\PhpstanTypeExtractor;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;
use Symfony\Component\Marshaller\Type\TypeExtractor;

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

        // Type extractors
        $container->register('marshaller.type_extractor.reflection', ReflectionTypeExtractor::class);
        $container->register('marshaller.type_extractor.phpstan', PhpstanTypeExtractor::class);

        $container->register('marshaller.type_extractor', TypeExtractor::class)
            ->setArguments([
                new Reference('marshaller.type_extractor.reflection'),
                new Reference('marshaller.type_extractor.phpstan'),
            ]);

        // Context builder
        $container->register('marshaller.native_context_builder.cache_path', CacheDirNativeContextBuilder::class)
            ->setArguments([
                new Parameter('marshaller.cache_dir'),
            ])
            ->addTag('marshaller.context.native_context_builder', ['priority' => 1000]);

        $container->register('marshaller.native_context_builder.type_extractor', TypeExtractorNativeContextBuilder::class)
            ->setArguments([
                new Reference('marshaller.type_extractor'),
            ])
            ->addTag('marshaller.context.native_context_builder', ['priority' => 1000]);

        $container->register('marshaller.native_context_builder.name_attribute', NameAttributeNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder', ['priority' => 900]);

        // $container->register('marshaller.native_context_builder.formatter_attribute', FormatterAttributeNativeContextBuilder::class)
        //     ->addTag('marshaller.context.native_context_builder', ['priority' => 900]);

        $container->register('marshaller.native_context_builder.property_name_formatter', PropertyNameFormatterNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder', ['priority' => 899]); // needs to be after name_attribute

        $container->register('marshaller.native_context_builder.name_formatter', PropertyValueFormatterNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder', ['priority' => 899]); // needs to be after formatter_attribute

        // $container->register('marshaller.native_context_builder.validate_data', ValidateDataNativeContextBuilder::class)
        //     ->addTag('marshaller.context.native_context_builder', ['priority' => 900]);
        //
        // $container->register('marshaller.native_context_builder.nullable_data', NullableDataNativeContextBuilder::class)
        //     ->addTag('marshaller.context.native_context_builder', ['priority' => 800]);
        //
        // $container->register('marshaller.native_context_builder.type', TypeNativeContextBuilder::class)
        //     ->addTag('marshaller.context.native_context_builder', ['priority' => 700]);
        //
        // $container->register('marshaller.native_context_builder.name', NameNativeContextBuilder::class)
        //     ->addTag('marshaller.context.native_context_builder', ['priority' => 600]);
        //
        // $container->register('marshaller.native_context_builder.phpstan_type.hook', PhpstanTypeHookNativeContextBuilder::class)
        //     ->addTag('marshaller.context.native_context_builder', ['priority' => 500]);
        //
        // $container->register('marshaller.native_context_builder.value_formatter', ValueFormatterNativeContextBuilder::class)
        //     ->addTag('marshaller.context.native_context_builder', ['priority' => 300]);
        //
        // $container->register('marshaller.native_context_builder.hook', HookNativeContextBuilder::class)
        //     ->addTag('marshaller.context.native_context_builder', ['priority' => 200]);

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
