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

use Symfony\Component\JsonEncoder\CacheWarmer\LazyGhostCacheWarmer;
use Symfony\Component\JsonEncoder\CacheWarmer\TemplateCacheWarmer;
use Symfony\Component\JsonEncoder\DataModel\Decode\DataModelBuilder as DecodeDataModelBuilder;
use Symfony\Component\JsonEncoder\DataModel\Encode\DataModelBuilder as EncodeDataModelBuilder;
use Symfony\Component\JsonEncoder\Instantiator\Instantiator;
use Symfony\Component\JsonEncoder\Instantiator\LazyInstantiator;
use Symfony\Component\JsonEncoder\JsonDecoder;
use Symfony\Component\JsonEncoder\JsonEncoder;
use Symfony\Component\JsonEncoder\Mapping\Decode\AttributePropertyMetadataLoader as DecodeAttributePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\Decode\DateTimeTypePropertyMetadataLoader as DecodeDateTimeTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\Encode\AttributePropertyMetadataLoader as EncodeAttributePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\Encode\DateTimeTypePropertyMetadataLoader as EncodeDateTimeTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\GenericTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Template\Decode\Template as DecodeTemplate;
use Symfony\Component\JsonEncoder\Template\Encode\Template as EncodeTemplate;

return static function (ContainerConfigurator $container) {
    $container->parameters()
        ->set('.json_encoder.cache_dir.template', '%kernel.cache_dir%/json_encoder/template')
        ->set('.json_encoder.cache_dir.lazy_ghost', '%kernel.cache_dir%/json_encoder/lazy_ghost')
    ;

    $container->services()
        // Encoder/Decoder
        ->set('json_encoder.encoder', JsonEncoder::class)
            ->args([
                service('.json_encoder.encode.template'),
                param('.json_encoder.cache_dir.template'),
                service('.json_encoder.runtime_services'),
            ])
        ->set('json.decoder', JsonDecoder::class)
            ->args([
                service('.json_encoder.decode.template'),
                service('json_encoder.instantiator'),
                service('json_encoder.instantiator.lazy'),
                param('.json_encoder.cache_dir.template'),
                service('.json_encoder.runtime_services'),
            ])
        ->alias(JsonEncoder::class, 'json_encoder.encoder')
        ->alias(JsonDecoder::class, 'json_encoder.decoder')

        // Data model
        ->set('.json_encoder.encode.data_model_builder', EncodeDataModelBuilder::class)
            ->args([
                service('json_encoder.encode.property_metadata_loader'),
                service('.json_encoder.runtime_services'),
            ])

        ->set('.json_encoder.decode.data_model_builder', DecodeDataModelBuilder::class)
            ->args([
                service('json_encoder.decode.property_metadata_loader'),
                service('.json_encoder.runtime_services'),
            ])

        // Metadata
        ->set('json_encoder.encode.property_metadata_loader', PropertyMetadataLoader::class)
            ->args([
                service('type_info.resolver'),
            ])

        ->set('.json_encoder.encode.property_metadata_loader.attribute', EncodeAttributePropertyMetadataLoader::class)
            ->decorate('json_encoder.encode.property_metadata_loader')
            ->args([
                service('.json_encoder.encode.property_metadata_loader.attribute.inner'),
                service('type_info.resolver'),
            ])

        ->set('.json_encoder.encode.property_metadata_loader.datetime_type', EncodeDateTimeTypePropertyMetadataLoader::class)
            ->decorate('json_encoder.encode.property_metadata_loader')
            ->args([
                service('.json_encoder.encode.property_metadata_loader.datetime_type.inner'),
            ])

        ->set('.json_encoder.encode.property_metadata_loader.generic_type', GenericTypePropertyMetadataLoader::class)
            ->decorate('json_encoder.encode.property_metadata_loader')
            ->args([
                service('.json_encoder.encode.property_metadata_loader.generic_type.inner'),
                service('type_info.resolver'),
            ])

        ->set('json_encoder.decode.property_metadata_loader', PropertyMetadataLoader::class)
            ->args([
                service('type_info.resolver'),
            ])

        ->set('.json_encoder.decode.property_metadata_loader.attribute', DecodeAttributePropertyMetadataLoader::class)
            ->decorate('json_encoder.decode.property_metadata_loader')
            ->args([
                service('.json_encoder.decode.property_metadata_loader.attribute.inner'),
                service('type_info.resolver'),
            ])

        ->set('.json_encoder.decode.property_metadata_loader.datetime_type', DecodeDateTimeTypePropertyMetadataLoader::class)
            ->decorate('json_encoder.decode.property_metadata_loader')
            ->args([
                service('.json_encoder.decode.property_metadata_loader.datetime_type.inner'),
            ])

        ->set('.json_encoder.decode.property_metadata_loader.generic_type', GenericTypePropertyMetadataLoader::class)
            ->decorate('json_encoder.decode.property_metadata_loader')
            ->args([
                service('.json_encoder.decode.property_metadata_loader.generic_type.inner'),
                service('type_info.resolver'),
            ])

        // Template
        ->set('.json_encoder.encode.template', EncodeTemplate::class)
            ->args([
                service('.json_encoder.encode.data_model_builder'),
                param('.json_encoder.cache_dir.template'),
            ])

        ->set('.json.decode.template', DecodeTemplate::class)
            ->args([
                service('.encoder.decode.data_model_builder'),
                param('.json.cache_dir.template'),
            ])

        // Cache
        ->set('.json_encoder.cache_warmer.template', TemplateCacheWarmer::class)
            ->args([
                abstract_arg('json encodable types'),
                service('.json_encoder.encode.template'),
                service('.json_encoder.decode.template'),
                param('.json_encoder.cache_dir.template'),
                service('logger')->ignoreOnInvalid(),
            ])
            ->tag('kernel.cache_warmer')

        ->set('.json_encoder.cache_warmer.lazy_ghost', LazyGhostCacheWarmer::class)
            ->args([
                abstract_arg('json encodable types'),
                param('.json_encoder.cache_dir.lazy_ghost'),
            ])
            ->tag('kernel.cache_warmer')

        // Instantiators
        ->set('json_encoder.instantiator', Instantiator::class)

        ->set('json_encoder.instantiator.lazy', LazyInstantiator::class)
            ->args([
                param('.json_encoder.cache_dir.lazy_ghost'),
            ])
    ;
};
