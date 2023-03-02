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
use Symfony\Component\Marshaller\Cache\LazyObjectCacheWarmer;
use Symfony\Component\Marshaller\Cache\TemplateCacheWarmer;
use Symfony\Component\Marshaller\CachedMarshallableResolver;
use Symfony\Component\Marshaller\Context\ContextBuilder\Generation as GenerationContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilder\Marshal as MarshalContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilder\Unmarshal as UnmarshalContextBuilder;
use Symfony\Component\Marshaller\Instantiator\LazyInstantiator;
use Symfony\Component\Marshaller\MarshallableResolver;
use Symfony\Component\Marshaller\MarshallableResolverInterface;
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
                new Parameter('marshaller.cache_dir.template'),
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
            ->setLazy(true)
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
            ->setArguments([
                new Reference('marshaller.marshallable_resolver'),
            ])
            ->addTag('marshaller.context_builder.generation', ['priority' => -128]);

        $container->register('marshaller.context_builder.generation.name_attribute.cached', GenerationContextBuilder\CachedNameAttributeContextBuilder::class)
            ->setDecoratedService('marshaller.context_builder.generation.name_attribute')
            ->setArguments([
                new Reference('.inner'),
                new Reference('cache.marshaller'),
            ]);

        $container->register('marshaller.context_builder.generation.formatter_attribute', GenerationContextBuilder\FormatterAttributeContextBuilder::class)
            ->setArguments([
                new Reference('marshaller.marshallable_resolver'),
            ])
            ->addTag('marshaller.context_builder.generation', ['priority' => -128]);

        $container->register('marshaller.context_builder.generation.formatter_attribute.cached', GenerationContextBuilder\CachedFormatterAttributeContextBuilder::class)
            ->setDecoratedService('marshaller.context_builder.generation.formatter_attribute')
            ->setArguments([
                new Reference('.inner'),
                new Reference('cache.marshaller'),
            ]);

        //
        // Marshal context builders
        //
        $container->register('marshaller.context_builder.marshal.json_encode_flags', MarshalContextBuilder\JsonEncodeFlagsContextBuilder::class)
            ->addTag('marshaller.context_builder.marshal', ['priority' => -128]);

        //
        // Unmarshal context builders
        //
        $container->register('marshaller.context_builder.unmarshal.hook', UnmarshalContextBuilder\HookContextBuilder::class)
            ->addTag('marshaller.context_builder.unmarshal', ['priority' => -128]);

        $container->register('marshaller.context_builder.unmarshal.collect_errors', UnmarshalContextBuilder\CollectErrorsContextBuilder::class)
            ->addTag('marshaller.context_builder.unmarshal', ['priority' => -128]);

        $container->register('marshaller.context_builder.unmarshal.mode', UnmarshalContextBuilder\ModeContextBuilder::class)
            ->addTag('marshaller.context_builder.unmarshal', ['priority' => -128]);

        $container->register('marshaller.context_builder.unmarshal.instantiator', UnmarshalContextBuilder\InstantiatorContextBuilder::class)
            ->setArguments([
                new Reference('marshaller.instantiator.lazy'),
            ])
            ->addTag('marshaller.context_builder.unmarshal', ['priority' => -128]);

        $container->register('marshaller.context_builder.unmarshal.union_selector', UnmarshalContextBuilder\UnionSelectorContextBuilder::class)
            ->addTag('marshaller.context_builder.unmarshal', ['priority' => -128]);

        $container->register('marshaller.context_builder.unmarshal.name_attribute', UnmarshalContextBuilder\NameAttributeContextBuilder::class)
            ->setArguments([
                new Reference('marshaller.marshallable_resolver'),
            ])
            ->addTag('marshaller.context_builder.unmarshal', ['priority' => -128]);

        $container->register('marshaller.context_builder.unmarshal.name_attribute.cached', UnmarshalContextBuilder\CachedNameAttributeContextBuilder::class)
            ->setDecoratedService('marshaller.context_builder.unmarshal.name_attribute')
            ->setArguments([
                new Reference('.inner'),
                new Reference('cache.marshaller'),
            ]);

        $container->register('marshaller.context_builder.unmarshal.formatter_attribute', UnmarshalContextBuilder\FormatterAttributeContextBuilder::class)
            ->setArguments([
                new Reference('marshaller.marshallable_resolver'),
            ])
            ->addTag('marshaller.context_builder.unmarshal', ['priority' => -64]); // must be triggered after "marshaller.context_builder.unmarshal.name_attribute"

        $container->register('marshaller.context_builder.unmarshal.formatter_attribute.cached', UnmarshalContextBuilder\CachedFormatterAttributeContextBuilder::class)
            ->setDecoratedService('marshaller.context_builder.unmarshal.formatter_attribute')
            ->setArguments([
                new Reference('.inner'),
                new Reference('cache.marshaller'),
            ]);

        //
        // Marshallable resolvers
        //
        $container->register('marshaller.marshallable_resolver', MarshallableResolver::class)
            ->setArguments([
                new Parameter('marshaller.marshallable_paths'),
            ]);

        $container->register('marshaller.marshallable_resolver.cached', CachedMarshallableResolver::class)
            ->setDecoratedService('marshaller.marshallable_resolver')
            ->setArguments([
                new Reference('.inner'),
                new Reference('cache.marshaller'),
            ]);

        $container->setAlias(MarshallableResolverInterface::class, 'marshaller.marshallable_resolver');

        //
        // Object instantiators
        //
        $container->register('marshaller.instantiator.lazy', LazyInstantiator::class)
            ->setArguments([
                new Parameter('marshaller.cache_dir.lazy_object'),
            ]);

        //
        // Cache
        //
        $container->register('marshaller.cache.template_warmer', TemplateCacheWarmer::class)
            ->setArguments([
                new Reference('marshaller.marshallable_resolver'),
                new Reference('marshaller'),
                new Parameter('marshaller.cache_dir.template'),
                new Parameter('marshaller.marshallable_formats'),
                new Parameter('marshaller.marshallable_nullable_data'),
                new Reference('logger'),
            ])
            ->addTag('kernel.cache_warmer');

        $container->register('marshaller.cache.lazy_object_warmer', LazyObjectCacheWarmer::class)
            ->setArguments([
                new Reference('marshaller.marshallable_resolver'),
                new Parameter('marshaller.cache_dir.lazy_object'),
            ])
            ->addTag('kernel.cache_warmer');
    }
}
