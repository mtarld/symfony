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
use Symfony\Component\Marshaller\Context\ContextBuilder\CachedFormatterAttributeContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilder\CachedNameAttributeContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilder\FormatterAttributeContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilder\HookContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilder\InstantiatorContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilder\NameAttributeContextBuilder;
use Symfony\Component\Marshaller\Hook\Marshal as MarshalHook;
use Symfony\Component\Marshaller\Hook\Unmarshal as UnmarshalHook;
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
                new TaggedIteratorArgument('marshaller.context_builder'),
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
        // Context builders
        //
        $container->register('marshaller.context_builder.hook', HookContextBuilder::class)
            ->setArguments([
                new TaggedIteratorArgument('marshaller.hook.marshal', 'name'),
                new TaggedIteratorArgument('marshaller.hook.unmarshal', 'name'),
            ])
            ->addTag('marshaller.context_builder');

        $container->register('marshaller.context_builder.instantiator', InstantiatorContextBuilder::class)
            ->setArguments([
                new Reference('marshaller.instantiator.lazy'),
            ])
            ->addTag('marshaller.context_builder');

        $container->register('marshaller.context_builder.name_attribute', NameAttributeContextBuilder::class)
            ->setArguments([
                new Reference('marshaller.marshallable_resolver'),
            ])
            ->addTag('marshaller.context_builder');

        $container->register('marshaller.context_builder.name_attribute.cached', CachedNameAttributeContextBuilder::class)
            ->setDecoratedService('marshaller.context_builder.name_attribute')
            ->setArguments([
                new Reference('.inner'),
                new Reference('cache.marshaller'),
            ]);

        $container->register('marshaller.context_builder.formatter_attribute', FormatterAttributeContextBuilder::class)
            ->setArguments([
                new Reference('marshaller.marshallable_resolver'),
                new Reference('cache.marshaller'),
            ])
            ->addTag('marshaller.context_builder');

        $container->register('marshaller.context_builder.formatter_attribute.cached', CachedFormatterAttributeContextBuilder::class)
            ->setDecoratedService('marshaller.context_builder.formatter_attribute')
            ->setArguments([
                new Reference('.inner'),
                new Reference('cache.marshaller'),
            ]);

        //
        // Hooks
        //
        $container->register('marshaller.hook.marshal.object', MarshalHook\ObjectHook::class)
            ->setArguments([
                new Reference('marshaller.type_extractor'),
            ])
            ->addTag('marshaller.hook.marshal', ['name' => 'object']);

        $container->register('marshaller.hook.marshal.property', MarshalHook\PropertyHook::class)
            ->setArguments([
                new Reference('marshaller.type_extractor'),
            ])
            ->addTag('marshaller.hook.marshal', ['name' => 'property']);

        $container->register('marshaller.hook.unmarshal.object', UnmarshalHook\ObjectHook::class)
            ->setArguments([
                new Reference('marshaller.type_extractor'),
            ])
            ->addTag('marshaller.hook.unmarshal', ['name' => 'object']);

        $container->register('marshaller.hook.unmarshal.property', UnmarshalHook\PropertyHook::class)
            ->setArguments([
                new Reference('marshaller.type_extractor'),
            ])
            ->addTag('marshaller.hook.unmarshal', ['name' => 'property']);

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
                new TaggedIteratorArgument('marshaller.context_builder'),
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
