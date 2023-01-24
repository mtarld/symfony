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
use Symfony\Component\Marshaller\Cache\MarshallableResolver;
use Symfony\Component\Marshaller\Cache\TemplateCacheWarmer;
use Symfony\Component\Marshaller\Context\ContextBuilder\Generation as GenerationContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilder\Marshal as MarshalContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilder\Unmarshal as UnmarshalContextBuilder;
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
                new TaggedIteratorArgument('marshaller.context_builder.marshal'),
                new TaggedIteratorArgument('marshaller.context_builder.generation'),
                new TaggedIteratorArgument('marshaller.context_builder.unmarshal'),
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
        $container->register('marshaller.context_builder.generation.hook', GenerationContextBuilder\HookContextBuilder::class)
            ->addTag('marshaller.context_builder.generation', ['priority' => -128]);

        $container->register('marshaller.context_builder.generation.type_formatter', GenerationContextBuilder\TypeFormatterContextBuilder::class)
            ->addTag('marshaller.context_builder.generation', ['priority' => -128]);

        $container->register('marshaller.context_builder.generation.name_attribute', GenerationContextBuilder\NameAttributeContextBuilder::class)
            ->addTag('marshaller.context_builder.generation', ['priority' => -128]);

        $container->register('marshaller.context_builder.generation.formatter_attribute', GenerationContextBuilder\FormatterAttributeContextBuilder::class)
            ->addTag('marshaller.context_builder.generation', ['priority' => -128]);

        //
        // Marshal context builders
        //
        $container->register('marshaller.context_builder.marshal.type', MarshalContextBuilder\TypeContextBuilder::class)
            ->addTag('marshaller.context_builder.marshal', ['priority' => -128]);

        $container->register('marshaller.context_builder.marshal.json_encode_flags', MarshalContextBuilder\JsonEncodeFlagsContextBuilder::class)
            ->addTag('marshaller.context_builder.marshal', ['priority' => -128]);

        //
        // Unmarshal context builders
        //
        $container->register('marshaller.context_builder.unmarshal.hook', UnmarshalContextBuilder\HookContextBuilder::class)
            ->addTag('marshaller.context_builder.unmarshal', ['priority' => -128]);
        $container->register('marshaller.context_builder.unmarshal.collect_errors', UnmarshalContextBuilder\CollectErrorsContextBuilder::class)
            ->addTag('marshaller.context_builder.unmarshal', ['priority' => -128]);
        $container->register('marshaller.context_builder.unmarshal.union_selector', UnmarshalContextBuilder\UnionSelectorContextBuilder::class)
            ->addTag('marshaller.context_builder.unmarshal', ['priority' => -128]);
        $container->register('marshaller.context_builder.unmarshal.name_attribute', UnmarshalContextBuilder\NameAttributeContextBuilder::class)
            ->addTag('marshaller.context_builder.unmarshal', ['priority' => -128]);
        $container->register('marshaller.context_builder.unmarshal.formatter_attribute', UnmarshalContextBuilder\FormatterAttributeContextBuilder::class)
            ->addTag('marshaller.context_builder.unmarshal', ['priority' => -64]); // must be triggered after "marshaller.context_builder.unmarshal.name_attribute"

        //
        // Cache
        //
        $container->register('marshaller.cache.marshallable_resolver', MarshallableResolver::class)
            ->setArguments([
                new Parameter('marshaller.marshallable_paths'),
            ]);

        $container->register('marshaller.cache.template_warmer', TemplateCacheWarmer::class)
            ->setArguments([
                new Reference('.marshaller.cache.marshallable_resolver'),
                new Reference('marshaller'),
                new Parameter('marshaller.cache_dir'),
                new Parameter('marshaller.marshallable_formats'),
                new Parameter('marshaller.marshallable_nullable_data'),
            ])
            ->addTag('kernel.cache_warmer');
    }
}