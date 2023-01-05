<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
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
                new TaggedIteratorArgument('marshaller.context.builder.marshal'),
                new TaggedIteratorArgument('marshaller.context.builder.generation'),
                new TaggedIteratorArgument('marshaller.context.builder.unmarshal'),
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
        $container->register('.marshaller.builder.generation.hook', HookContextBuilder::class)
            ->addTag('marshaller.context.builder.generation', ['priority' => -128]);

        $container->register('.marshaller.builder.generation.type_formatter', TypeFormatterContextBuilder::class)
            ->addTag('marshaller.context.builder.generation', ['priority' => -128]);

        $container->register('.marshaller.builder.generation.name_attribute', NameAttributeContextBuilder::class)
            ->addTag('marshaller.context.builder.generation', ['priority' => -128]);

        $container->register('.marshaller.builder.generation.formatter_attribute', FormatterAttributeContextBuilder::class)
            ->addTag('marshaller.context.builder.generation', ['priority' => -128]);

        //
        // Marshal context builders
        //
        $container->register('.marshaller.builder.marshal.type', TypeContextBuilder::class)
            ->addTag('marshaller.context.builder.marshal', ['priority' => -128]);

        $container->register('.marshaller.builder.marshal.json_encode_flags', JsonEncodeFlagsContextBuilder::class)
            ->addTag('marshaller.context.builder.marshal', ['priority' => -128]);

        //
        // Unmarshal context builders
        //
        $container->register('.marshaller.builder.unmarshal.formatter_attribute', FormatterAttributeContextBuilder::class)
            ->addTag('marshaller.context.builder.unmarshal', ['priority' => -128]);

        //
        // Cache
        //
        $container->register('.marshaller.cache.warmable_resolver', WarmableResolver::class)
            ->setArguments([
                new Parameter('marshaller.warmable_paths'),
            ]);

        $container->register('.marshaller.cache.template_warmer', TemplateCacheWarmer::class)
            ->setArguments([
                new Reference('.marshaller.cache.warmable_resolver'),
                new Reference('marshaller'),
                new Parameter('marshaller.cache_dir'),
                new Parameter('marshaller.warmable_formats'),
                new Parameter('marshaller.warmable_nullable_data'),
            ])
            ->addTag('kernel.cache_warmer');
    }
}
