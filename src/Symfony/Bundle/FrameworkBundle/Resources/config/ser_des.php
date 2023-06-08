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

use Symfony\Bundle\FrameworkBundle\CacheWarmer\SerDesCacheWarmer;
use Symfony\Component\SerDes\Context\ContextBuilder\Deserialize\DeserializeFormatterAttributeContextBuilder;
use Symfony\Component\SerDes\Context\ContextBuilder\Deserialize\GroupsAttributeContextBuilder as DeserializeGroupsAttributeContextBuilder;
use Symfony\Component\SerDes\Context\ContextBuilder\Deserialize\DeserializeHookContextBuilder;
use Symfony\Component\SerDes\Context\ContextBuilder\Deserialize\DeserializeInstantiatorContextBuilder;
use Symfony\Component\SerDes\Context\ContextBuilder\Deserialize\SerializedNameAttributeContextBuilder as DeserializeSerializedNameAttributeContextBuilder;
use Symfony\Component\SerDes\Context\ContextBuilder\Serialize\SerializeFormatterAttributeContextBuilder;
use Symfony\Component\SerDes\Context\ContextBuilder\Serialize\GroupsAttributeContextBuilder as SerializeGroupsAttributeContextBuilder;
use Symfony\Component\SerDes\Context\ContextBuilder\Serialize\SerializeHookContextBuilder;
use Symfony\Component\SerDes\Context\ContextBuilder\Serialize\SerializedNameAttributeContextBuilder as SerializeSerializedNameAttributeContextBuilder;
use Symfony\Component\SerDes\Instantiator\LazyInstantiator;
use Symfony\Component\SerDes\SerializableResolver\CachedSerializableResolver;
use Symfony\Component\SerDes\SerializableResolver\PathSerializableResolver;
use Symfony\Component\SerDes\SerializableResolver\SerializableResolverInterface;
use Symfony\Component\SerDes\Serializer;
use Symfony\Component\SerDes\SerializerInterface;
use Symfony\Component\SerDes\Type\PhpstanTypeExtractor;
use Symfony\Component\SerDes\Type\ReflectionTypeExtractor;
use Symfony\Component\SerDes\Type\TypeExtractorInterface;
use Symfony\Component\SerDes\Hook\Serialize as SerializeHook;
use Symfony\Component\SerDes\Hook\Deserialize as DeserializeHook;

return static function (ContainerConfigurator $container) {
    $container->parameters()
        ->set('.ser_des.cache_dir.template', '%kernel.cache_dir%/ser_des/template')
        ->set('.ser_des.cache_dir.lazy_object', '%kernel.cache_dir%/ser_des/lazy_object')
    ;

    $serializeContextBuilders = [
        service('.ser_des.context_builder.serialize.hook'),
        service('.ser_des.context_builder.serialize.name_attribute'),
        service('.ser_des.context_builder.serialize.formatter_attribute'),
        service('.ser_des.context_builder.serialize.groups_attribute'),
    ];

    $deserializeContextBuilders = [
        service('.ser_des.context_builder.deserialize.hook'),
        service('.ser_des.context_builder.deserialize.name_attribute'),
        service('.ser_des.context_builder.deserialize.formatter_attribute'),
        service('.ser_des.context_builder.deserialize.groups_attribute'),
        service('.ser_des.context_builder.deserialize.instantiator'),
    ];

    $container->services()
        // Serializer
        ->set('ser_des.serializer', Serializer::class)
            ->args([
                param('.ser_des.cache_dir.template'),
            ])
            ->call('setSerializeContextBuilders', [$serializeContextBuilders])
            ->call('setDeserializeContextBuilders', [$deserializeContextBuilders])

        ->alias(SerializerInterface::class, 'ser_des.serializer')

        // Type extractors
        ->set('ser_des.type_extractor.reflection', ReflectionTypeExtractor::class)
            ->lazy()
            ->tag('proxy', ['interface' => TypeExtractorInterface::class])

        ->set('ser_des.type_extractor.phpstan', PhpstanTypeExtractor::class)
            ->decorate('ser_des.type_extractor.reflection')
            ->args([
                service('ser_des.type_extractor.phpstan.inner'),
            ])
            ->lazy()
            ->tag('proxy', ['interface' => TypeExtractorInterface::class])

        ->alias('ser_des.type_extractor', 'ser_des.type_extractor.reflection')

        // Context builders
        ->set('.ser_des.context_builder.serialize.hook', SerializeHookContextBuilder::class)
            ->args([[
                'object' => service('ser_des.hook.serialize.object'),
                'property' => service('ser_des.hook.serialize.property'),
            ]])

        ->set('.ser_des.context_builder.serialize.name_attribute', SerializeSerializedNameAttributeContextBuilder::class)
            ->args([
                service('ser_des.serializable_resolver'),
            ])

        ->set('.ser_des.context_builder.serialize.formatter_attribute', SerializeFormatterAttributeContextBuilder::class)
            ->args([
                service('ser_des.serializable_resolver'),
            ])

        ->set('.ser_des.context_builder.serialize.groups_attribute', SerializeGroupsAttributeContextBuilder::class)
            ->args([
                service('ser_des.serializable_resolver'),
            ])

        ->set('.ser_des.context_builder.deserialize.hook', DeserializeHookContextBuilder::class)
            ->args([[
                'object' => service('ser_des.hook.deserialize.object'),
                'property' => service('ser_des.hook.deserialize.property'),
            ]])

        ->set('.ser_des.context_builder.deserialize.name_attribute', DeserializeSerializedNameAttributeContextBuilder::class)
            ->args([
                service('ser_des.serializable_resolver'),
            ])

        ->set('.ser_des.context_builder.deserialize.formatter_attribute', DeserializeFormatterAttributeContextBuilder::class)
            ->args([
                service('ser_des.serializable_resolver'),
            ])

        ->set('.ser_des.context_builder.deserialize.groups_attribute', DeserializeGroupsAttributeContextBuilder::class)
            ->args([
                service('ser_des.serializable_resolver'),
            ])

        ->set('.ser_des.context_builder.deserialize.instantiator', DeserializeInstantiatorContextBuilder::class)
            ->args([
                service('ser_des.instantiator.lazy'),
            ])

        // Hooks
        ->set('ser_des.hook.serialize.object', SerializeHook\ObjectHook::class)
            ->args([
                service('ser_des.type_extractor'),
            ])

        ->set('ser_des.hook.deserialize.object', DeserializeHook\ObjectHook::class)
            ->args([
                service('ser_des.type_extractor'),
            ])

        // Serializable resolvers
        ->set('ser_des.serializable_resolver', PathSerializableResolver::class)
            ->args([
                param('ser_des.serializable_paths'),
            ])

        ->set('ser_des.serializable_resolver.cached', CachedSerializableResolver::class)
            ->decorate('ser_des.serializable_resolver', priority: -1024)
            ->args([
                service('ser_des.serializable_resolver.cached.inner'),
                service('cache.ser_des')->ignoreOnInvalid(),
            ])

        ->alias(SerializableResolverInterface::class, 'ser_des.serializable_resolver')

        // Object instantiators
        ->set('ser_des.instantiator.lazy', LazyInstantiator::class)
            ->args([
                param('.ser_des.cache_dir.lazy_object'),
            ])

        // Cache
        ->set('ser_des.cache_warmer', SerDesCacheWarmer::class)
            ->args([
                service('ser_des.serializable_resolver'),
                param('.ser_des.cache_dir.template'),
                param('.ser_des.cache_dir.lazy_object'),
                param('ser_des.template_warm_up.formats'),
                param('ser_des.template_warm_up.max_variants'),
                service('logger')->ignoreOnInvalid(),
            ])
            ->call('setContextBuilders', [$serializeContextBuilders])
            ->tag('kernel.cache_warmer')
    ;
};
