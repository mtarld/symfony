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
use Symfony\Component\Marshaller\NativeContext\FormatterAttributeNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\HookNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\NameAttributeNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\PropertyNameFormatterNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\PropertyTypeNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\PropertyValueFormatterNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\TypeExtractorNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\TypeNativeContextBuilder;
use Symfony\Component\Marshaller\NativeContext\TypeValueFormatterNativeContextBuilder;
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

        $container->register('marshaller.native_context_builder.type_value_formatter', TypeValueFormatterNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder', ['priority' => 1000]);

        $container->register('marshaller.native_context_builder.type_extractor', TypeExtractorNativeContextBuilder::class)
            ->setArguments([
                new Reference('marshaller.type_extractor'),
            ])
            ->addTag('marshaller.context.native_context_builder', ['priority' => -100]);

        $container->register('marshaller.native_context_builder.name_attribute', NameAttributeNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder', ['priority' => -100]);

        $container->register('marshaller.native_context_builder.formatter_attribute', FormatterAttributeNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder', ['priority' => -100]);

        $container->register('marshaller.native_context_builder.property_type', PropertyTypeNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder', ['priority' => -100]);

        $container->register('marshaller.native_context_builder.type', TypeNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder', ['priority' => -100]);

        $container->register('marshaller.native_context_builder.property_name_formatter', PropertyNameFormatterNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder', ['priority' => -200]); // need to be after name_attribute

        $container->register('marshaller.native_context_builder.property_value_formatter', PropertyValueFormatterNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder', ['priority' => -200]); // need to be after formatter_attribute

        $container->register('marshaller.native_context_builder.hook', HookNativeContextBuilder::class)
            ->addTag('marshaller.context.native_context_builder', ['priority' => -1000]); // need to be applied last (to be able to override generated hooks)

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
