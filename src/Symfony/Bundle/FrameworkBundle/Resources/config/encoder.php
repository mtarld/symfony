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

use Symfony\Component\Encoder\CacheWarmer\LazyGhostCacheWarmer;
use Symfony\Component\Encoder\DataModel\Decode\DataModelBuilder as DecodeDataModelBuilder;
use Symfony\Component\Encoder\DataModel\Encode\DataModelBuilder as EncodeDataModelBuilder;
use Symfony\Component\Encoder\Instantiator\Instantiator;
use Symfony\Component\Encoder\Instantiator\LazyInstantiator;
use Symfony\Component\Encoder\Mapping\Decode\AttributePropertyMetadataLoader as DecodeAttributePropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\Decode\DateTimeTypePropertyMetadataLoader as DecodeDateTimeTypePropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\Encode\AttributePropertyMetadataLoader as EncodeAttributePropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\Encode\DateTimeTypePropertyMetadataLoader as EncodeDateTimeTypePropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\GenericTypePropertyMetadataLoader;
use Symfony\Component\Encoder\Mapping\PropertyMetadataLoader;

return static function (ContainerConfigurator $container) {
    $container->parameters()
        ->set('.encoder.cache_dir.lazy_ghost', '%kernel.cache_dir%/encoder/lazy_ghost')
    ;

    $container->services()
        // Data model
        ->set('.encoder.encode.data_model_builder', EncodeDataModelBuilder::class)
            ->args([
                service('encoder.encode.property_metadata_loader'),
                service('.encoder.runtime_services'),
            ])

        ->set('.encoder.decode.data_model_builder', DecodeDataModelBuilder::class)
            ->args([
                service('encoder.decode.property_metadata_loader'),
                service('.encoder.runtime_services'),
            ])

        // Metadata
        ->set('encoder.encode.property_metadata_loader', PropertyMetadataLoader::class)
            ->args([
                service('type_info.resolver'),
            ])

        ->set('.encoder.encode.property_metadata_loader.attribute', EncodeAttributePropertyMetadataLoader::class)
            ->decorate('encoder.encode.property_metadata_loader')
            ->args([
                service('.encoder.encode.property_metadata_loader.attribute.inner'),
                service('type_info.resolver'),
            ])

        ->set('.encoder.encode.property_metadata_loader.datetime_type', EncodeDateTimeTypePropertyMetadataLoader::class)
            ->decorate('encoder.encode.property_metadata_loader')
            ->args([
                service('.encoder.encode.property_metadata_loader.datetime_type.inner'),
            ])

        ->set('.encoder.encode.property_metadata_loader.generic_type', GenericTypePropertyMetadataLoader::class)
            ->decorate('encoder.encode.property_metadata_loader')
            ->args([
                service('.encoder.encode.property_metadata_loader.generic_type.inner'),
                service('type_info.resolver'),
            ])

        ->set('encoder.decode.property_metadata_loader', PropertyMetadataLoader::class)
            ->args([
                service('type_info.resolver'),
            ])

        ->set('.encoder.decode.property_metadata_loader.attribute', DecodeAttributePropertyMetadataLoader::class)
            ->decorate('encoder.decode.property_metadata_loader')
            ->args([
                service('.encoder.decode.property_metadata_loader.attribute.inner'),
                service('type_info.resolver'),
            ])

        ->set('.encoder.decode.property_metadata_loader.datetime_type', DecodeDateTimeTypePropertyMetadataLoader::class)
            ->decorate('encoder.decode.property_metadata_loader')
            ->args([
                service('.encoder.decode.property_metadata_loader.datetime_type.inner'),
            ])

        ->set('.encoder.decode.property_metadata_loader.generic_type', GenericTypePropertyMetadataLoader::class)
            ->decorate('encoder.decode.property_metadata_loader')
            ->args([
                service('.encoder.decode.property_metadata_loader.generic_type.inner'),
                service('type_info.resolver'),
            ])

        ->set('.encoder.cache_warmer.lazy_ghost', LazyGhostCacheWarmer::class)
            ->args([
                abstract_arg('encodable types'),
                param('.encoder.cache_dir.lazy_ghost'),
            ])
            ->tag('kernel.cache_warmer')

        // Instantiators
        ->set('encoder.instantiator', Instantiator::class)

        ->set('encoder.instantiator.lazy', LazyInstantiator::class)
            ->args([
                param('.encoder.cache_dir.lazy_ghost'),
            ])
    ;
};
