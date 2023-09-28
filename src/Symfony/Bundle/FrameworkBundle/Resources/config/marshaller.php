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

use Symfony\Component\JsonMarshaller\CacheWarmer\LazyGhostCacheWarmer;
use Symfony\Component\JsonMarshaller\Marshal\DataModel\DataModelBuilder as MarshalDataModelBuilder;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\AttributePropertyMetadataLoader as MarshalAttributePropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\PropertyMetadataLoader as MarshalPropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\PropertyMetadataLoaderInterface as MarshalPropertyMetadataLoaderInterface;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\TypePropertyMetadataLoader as MarshalTypePropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Type\ReflectionTypeExtractor;
use Symfony\Component\JsonMarshaller\Type\TypeExtractorInterface;
use Symfony\Component\JsonMarshaller\Unmarshal\DataModel\DataModelBuilder as UnmarshalDataModelBuilder;
use Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\EagerInstantiator;
use Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\LazyInstantiator;
use Symfony\Component\JsonMarshaller\Unmarshal\Mapping\AttributePropertyMetadataLoader as UnmarshalAttributePropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Unmarshal\Mapping\PropertyMetadataLoader as UnmarshalPropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Unmarshal\Mapping\PropertyMetadataLoaderInterface as UnmarshalPropertyMetadataLoaderInterface;
use Symfony\Component\JsonMarshaller\Unmarshal\Mapping\TypePropertyMetadataLoader as UnmarshalTypePropertyMetadataLoader;

return static function (ContainerConfigurator $container) {
    $container->parameters()
        ->set('.marshaller.cache_dir.lazy_ghost', '%kernel.cache_dir%/marshaller/lazy_ghost')
    ;

    $container->services()
        // Data model
        ->set('.marshaller.marshal.data_model_builder', MarshalDataModelBuilder::class)
            ->args([
                service('marshaller.marshal.property_metadata_loader'),
                abstract_arg('marshal runtime services'),
            ])

        ->set('.marshaller.unmarshal.data_model_builder', UnmarshalDataModelBuilder::class)
            ->args([
                service('marshaller.unmarshal.property_metadata_loader'),
                abstract_arg('unmarshal runtime services'),
            ])

        // Metadata
        ->set('marshaller.marshal.property_metadata_loader', MarshalPropertyMetadataLoader::class)
            ->args([
                service('marshaller.type_extractor'),
            ])

        ->set('.marshaller.marshal.property_metadata_loader.attribute', MarshalAttributePropertyMetadataLoader::class)
            ->decorate('marshaller.marshal.property_metadata_loader')
            ->args([
                service('.marshaller.marshal.property_metadata_loader.attribute.inner'),
                service('marshaller.type_extractor'),
            ])

        ->set('.marshaller.marshal.property_metadata_loader.type', MarshalTypePropertyMetadataLoader::class)
            ->decorate('marshaller.marshal.property_metadata_loader')
            ->args([
                service('.marshaller.marshal.property_metadata_loader.type.inner'),
                service('marshaller.type_extractor'),
            ])

        ->alias(MarshalPropertyMetadataLoaderInterface::class, 'marshaller.marshal.property_metadata_loader')

        ->set('marshaller.unmarshal.property_metadata_loader', UnmarshalPropertyMetadataLoader::class)
            ->args([
                service('marshaller.type_extractor'),
            ])

        ->set('.marshaller.unmarshal.property_metadata_loader.attribute', UnmarshalAttributePropertyMetadataLoader::class)
            ->decorate('marshaller.unmarshal.property_metadata_loader')
            ->args([
                service('.marshaller.unmarshal.property_metadata_loader.attribute.inner'),
                service('marshaller.type_extractor'),
            ])

        ->set('.marshaller.unmarshal.property_metadata_loader.type', UnmarshalTypePropertyMetadataLoader::class)
            ->decorate('marshaller.unmarshal.property_metadata_loader')
            ->args([
                service('.marshaller.unmarshal.property_metadata_loader.type.inner'),
                service('marshaller.type_extractor'),
            ])

        ->alias(UnmarshalPropertyMetadataLoaderInterface::class, 'marshaller.unmarshal.property_metadata_loader')

        ->set('.marshaller.cache_warmer.lazy_ghost', LazyGhostCacheWarmer::class)
            ->args([
                abstract_arg('marshallable types'),
                param('.marshaller.cache_dir.lazy_ghost'),
            ])
            ->tag('kernel.cache_warmer')

        // Instantiators
        ->set('marshaller.instantiator.eager', EagerInstantiator::class)

        ->set('marshaller.instantiator.lazy', LazyInstantiator::class)
            ->args([
                param('.marshaller.cache_dir.lazy_ghost'),
            ])

        // Type extractors
        ->set('marshaller.type_extractor.reflection', ReflectionTypeExtractor::class)
            ->lazy()
            ->tag('proxy', ['interface' => TypeExtractorInterface::class])

        ->alias('marshaller.type_extractor', 'marshaller.type_extractor.reflection')
        ->alias(TypeExtractorInterface::class, 'marshaller.type_extractor')
    ;
};
