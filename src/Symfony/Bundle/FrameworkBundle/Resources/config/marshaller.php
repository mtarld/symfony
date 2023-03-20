<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Bundle\FrameworkBundle\CacheWarmer\MarshallerCacheWarmer;
use Symfony\Component\Marshaller\CachedMarshallableResolver;
use Symfony\Component\Marshaller\Context\ContextBuilder\CachedContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilder\FormatterAttributeContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilder\HookContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilder\InstantiatorContextBuilder;
use Symfony\Component\Marshaller\Context\ContextBuilder\NameAttributeContextBuilder;
use Symfony\Component\Marshaller\Instantiator\LazyInstantiator;
use Symfony\Component\Marshaller\MarshallableResolver;
use Symfony\Component\Marshaller\MarshallableResolverInterface;
use Symfony\Component\Marshaller\Marshaller;
use Symfony\Component\Marshaller\MarshallerInterface;
use Symfony\Component\Marshaller\Type\PhpstanTypeExtractor;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;
use Symfony\Component\Marshaller\Type\TypeExtractorInterface;
use Symfony\Component\Marshaller\Hook\Marshal as MarshalHook;
use Symfony\Component\Marshaller\Hook\Unmarshal as UnmarshalHook;

return static function (ContainerConfigurator $container) {
    $container->parameters()
        ->set('marshaller.cache_dir.template', '%kernel.cache_dir%/marshaller/template')
        ->set('marshaller.cache_dir.lazy_object', '%kernel.cache_dir%/marshaller/lazy_object')
    ;

    $container->services()
        // Marshaller
        ->set('marshaller', Marshaller::class)
            ->args([
                tagged_iterator('marshaller.context_builder'),
                param('marshaller.cache_dir.template')
            ])
        ->alias(MarshallerInterface::class, 'marshaller')

        // Type extractors
        ->set('marshaller.type_extractor.reflection', ReflectionTypeExtractor::class)
            ->lazy()
            ->tag('proxy', ['interface' => TypeExtractorInterface::class])

        ->set('marshaller.type_extractor.phpstan', PhpstanTypeExtractor::class)
            ->decorate('marshaller.type_extractor.reflection')
            ->args([
                service('marshaller.type_extractor.phpstan.inner'),
            ])
            ->lazy()
            ->tag('proxy', ['interface' => TypeExtractorInterface::class])

        ->alias('marshaller.type_extractor', 'marshaller.type_extractor.reflection')

        // Context builders
        ->set('marshaller.context_builder.hook', HookContextBuilder::class)
            ->args([
                tagged_iterator('marshaller.hook.marshal', 'name'),
                tagged_iterator('marshaller.hook.unmarshal', 'name'),
            ])
            ->tag('marshaller.context_builder', ['priority' => -1024])

        ->set('marshaller.context_builder.instantiator', InstantiatorContextBuilder::class)
            ->args([
                service('marshaller.instantiator.lazy'),
            ])
            ->tag('marshaller.context_builder', ['priority' => -1024])

        ->set('marshaller.context_builder.name_attribute', NameAttributeContextBuilder::class)
            ->args([
                service('marshaller.marshallable_resolver'),
            ])
            ->tag('marshaller.context_builder', ['priority' => -1024])

        ->set('marshaller.context_builder.name_attribute.cached', CachedContextBuilder::class)
            ->decorate('marshaller.context_builder.name_attribute')
            ->args([
                service('marshaller.context_builder.name_attribute.cached.inner'),
                'marshaller.context.name_attribute',
                'property_name',
                service('cache.marshaller')->ignoreOnInvalid(),
            ])

        ->set('marshaller.context_builder.formatter_attribute', FormatterAttributeContextBuilder::class)
            ->args([
                service('marshaller.marshallable_resolver'),
            ])
            ->tag('marshaller.context_builder', ['priority' => -1024])

        ->set('marshaller.context_builder.formatter_attribute.cached', CachedContextBuilder::class)
            ->decorate('marshaller.context_builder.formatter_attribute')
            ->args([
                service('marshaller.context_builder.formatter_attribute.cached.inner'),
                'marshaller.context.formatter_attribute',
                'property_formatter',
                service('cache.marshaller')->ignoreOnInvalid(),
            ])

        // Hooks
        ->set('marshaller.hook.marshal.object', MarshalHook\ObjectHook::class)
            ->args([
                service('marshaller.type_extractor'),
            ])
            ->tag('marshaller.hook.marshal', ['name' => 'object'])

        ->set('marshaller.hook.marshal.property', MarshalHook\PropertyHook::class)
            ->args([
                service('marshaller.type_extractor'),
            ])
            ->tag('marshaller.hook.marshal', ['name' => 'property'])

        ->set('marshaller.hook.unmarshal.object', UnmarshalHook\ObjectHook::class)
            ->args([
                service('marshaller.type_extractor'),
            ])
            ->tag('marshaller.hook.unmarshal', ['name' => 'object'])

        ->set('marshaller.hook.unmarshal.property', UnmarshalHook\PropertyHook::class)
            ->args([
                service('marshaller.type_extractor'),
            ])
            ->tag('marshaller.hook.unmarshal', ['name' => 'property'])

        // Marshallable resolvers
        ->set('marshaller.marshallable_resolver', MarshallableResolver::class)
            ->args([
                param('marshaller.marshallable_paths'),
            ])

        ->set('marshaller.marshallable_resolver.cached', CachedMarshallableResolver::class)
            ->decorate('marshaller.marshallable_resolver')
            ->args([
                service('marshaller.marshallable_resolver.cached.inner'),
                service('cache.marshaller')->ignoreOnInvalid(),
            ])

        ->alias(MarshallableResolverInterface::class, 'marshaller.marshallable_resolver')

        // Object instantiators
        ->set('marshaller.instantiator.lazy', LazyInstantiator::class)
            ->args([
                param('marshaller.cache_dir.lazy_object'),
            ])

        // Cache
        ->set('marshaller.cache_warmer', MarshallerCacheWarmer::class)
            ->args([
                service('marshaller.marshallable_resolver'),
                tagged_iterator('marshaller.context_builder'),
                param('marshaller.cache_dir.template'),
                param('marshaller.cache_dir.lazy_object'),
                param('marshaller.template_warm_up.formats'),
                param('marshaller.template_warm_up.nullable_data'),
                service('logger')->ignoreOnInvalid(),
            ])
            ->tag('kernel.cache_warmer')
    ;
};
