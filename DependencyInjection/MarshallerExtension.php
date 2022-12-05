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
use Symfony\Component\Marshaller\Context\Generation\FormatterAttributeContextBuilder;
use Symfony\Component\Marshaller\Context\Generation\HookContextBuilder;
use Symfony\Component\Marshaller\Context\Generation\NameAttributeContextBuilder;
use Symfony\Component\Marshaller\Context\Generation\TypeFormatterContextBuilder;
use Symfony\Component\Marshaller\Context\Marshal\JsonEncodeFlagsContextBuilder;
use Symfony\Component\Marshaller\Context\Marshal\TypeContextBuilder;
use Symfony\Component\Marshaller\Marshaller;
use Symfony\Component\Marshaller\MarshallerInterface;
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
                new TaggedIteratorArgument('marshaller.context.context_builder.marshal'),
                new TaggedIteratorArgument('marshaller.context.context_builder.generation'),
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
        $container->register('marshaller.context_builder.generation.hook', HookContextBuilder::class)
            ->addTag('marshaller.context.context_builder.generation', ['priority' => -128]);

        $container->register('marshaller.context_builder.generation.type_formatter', TypeFormatterContextBuilder::class)
            ->addTag('marshaller.context.context_builder.generation', ['priority' => -128]);

        $container->register('marshaller.context_builder.generation.name_attribute', NameAttributeContextBuilder::class)
            ->addTag('marshaller.context.context_builder.generation', ['priority' => -128]);

        $container->register('marshaller.context_builder.generation.formatter_attribute', FormatterAttributeContextBuilder::class)
            ->addTag('marshaller.context.context_builder.generation', ['priority' => -128]);

        //
        // Marshal context builders
        //
        $container->register('marshaller.context_builder.marshal.type', TypeContextBuilder::class)
            ->addTag('marshaller.context.context_builder.marshal', ['priority' => -128]);

        $container->register('marshaller.context_builder.marshal.json_encode_flags', JsonEncodeFlagsContextBuilder::class)
            ->addTag('marshaller.context.context_builder.marshal', ['priority' => -128]);

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
