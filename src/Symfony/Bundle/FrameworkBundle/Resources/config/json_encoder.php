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

use BcMath\Number;
use Symfony\Component\JsonEncoder\CacheWarmer\EncoderDecoderCacheWarmer;
use Symfony\Component\JsonEncoder\CacheWarmer\LazyGhostCacheWarmer;
use Symfony\Component\JsonEncoder\JsonDecoder;
use Symfony\Component\JsonEncoder\JsonEncoder;
use Symfony\Component\JsonEncoder\Mapping\Decode\AttributePropertyMetadataLoader as DecodeAttributePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\Decode\DateTimeTypePropertyMetadataLoader as DecodeDateTimeTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\Decode\NumberTypePropertyMetadataLoader as DecodeNumberTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\Encode\AttributePropertyMetadataLoader as EncodeAttributePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\Encode\DateTimeTypePropertyMetadataLoader as EncodeDateTimeTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\Encode\NumberTypePropertyMetadataLoader as EncodeNumberTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\GenericTypePropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonEncoder\ValueTransformer\DateTimeToStringValueTransformer;
use Symfony\Component\JsonEncoder\ValueTransformer\NumberToStringValueTransformer;
use Symfony\Component\JsonEncoder\ValueTransformer\ScalarToNumberValueTransformer;
use Symfony\Component\JsonEncoder\ValueTransformer\StringToDateTimeValueTransformer;

return static function (ContainerConfigurator $container) {
    $container->services()
        // encoder/decoder
        ->set('json_encoder.encoder', JsonEncoder::class)
            ->args([
                tagged_locator('json_encoder.value_transformer'),
                service('json_encoder.encode.property_metadata_loader'),
                param('.json_encoder.encoders_dir'),
            ])
        ->set('json_encoder.decoder', JsonDecoder::class)
            ->args([
                tagged_locator('json_encoder.value_transformer'),
                service('json_encoder.decode.property_metadata_loader'),
                param('.json_encoder.decoders_dir'),
                param('.json_encoder.lazy_ghosts_dir'),
            ])
        ->alias(JsonEncoder::class, 'json_encoder.encoder')
        ->alias(JsonDecoder::class, 'json_encoder.decoder')

        // metadata
        ->set('json_encoder.encode.property_metadata_loader', PropertyMetadataLoader::class)
            ->args([
                service('type_info.resolver'),
            ])
        ->set('.json_encoder.encode.property_metadata_loader.generic', GenericTypePropertyMetadataLoader::class)
            ->decorate('json_encoder.encode.property_metadata_loader')
            ->args([
                service('.inner'),
                service('type_info.type_context_factory'),
            ])
        ->set('.json_encoder.encode.property_metadata_loader.date_time', EncodeDateTimeTypePropertyMetadataLoader::class)
            ->decorate('json_encoder.encode.property_metadata_loader')
            ->args([
                service('.inner'),
            ])
        ->set('.json_encoder.encode.property_metadata_loader.number', EncodeNumberTypePropertyMetadataLoader::class)
            ->decorate('json_encoder.encode.property_metadata_loader')
            ->args([
                service('.inner'),
            ])
        ->set('.json_encoder.encode.property_metadata_loader.attribute', EncodeAttributePropertyMetadataLoader::class)
            ->decorate('json_encoder.encode.property_metadata_loader')
            ->args([
                service('.inner'),
                tagged_locator('json_encoder.value_transformer'),
                service('type_info.resolver'),
            ])

        ->set('json_encoder.decode.property_metadata_loader', PropertyMetadataLoader::class)
            ->args([
                service('type_info.resolver'),
            ])
        ->set('.json_encoder.decode.property_metadata_loader.generic', GenericTypePropertyMetadataLoader::class)
            ->decorate('json_encoder.decode.property_metadata_loader')
            ->args([
                service('.inner'),
                service('type_info.type_context_factory'),
            ])
        ->set('.json_encoder.decode.property_metadata_loader.date_time', DecodeDateTimeTypePropertyMetadataLoader::class)
            ->decorate('json_encoder.decode.property_metadata_loader')
            ->args([
                service('.inner'),
            ])
        ->set('.json_encoder.decode.property_metadata_loader.number', DecodeNumberTypePropertyMetadataLoader::class)
            ->decorate('json_encoder.decode.property_metadata_loader')
            ->args([
                service('.inner'),
            ])
        ->set('.json_encoder.decode.property_metadata_loader.attribute', DecodeAttributePropertyMetadataLoader::class)
            ->decorate('json_encoder.decode.property_metadata_loader')
            ->args([
                service('.inner'),
                tagged_locator('json_encoder.value_transformer'),
                service('type_info.resolver'),
            ])

        // value transformers
        ->set('json_encoder.value_transformer.date_time_to_string', DateTimeToStringValueTransformer::class)
            ->tag('json_encoder.value_transformer')

        ->set('json_encoder.value_transformer.string_to_date_time', StringToDateTimeValueTransformer::class)
            ->tag('json_encoder.value_transformer')

        ->set('json_encoder.value_transformer.number_to_string', NumberToStringValueTransformer::class)
            ->tag('json_encoder.value_transformer')

        ->set('json_encoder.value_transformer.scalar_to_bc_math_number', ScalarToNumberValueTransformer::class)
            ->args([Number::class])
            ->tag('json_encoder.value_transformer')

        ->set('json_encoder.value_transformer.scalar_to_gmp_number', ScalarToNumberValueTransformer::class)
            ->args([\GMP::class])
            ->tag('json_encoder.value_transformer')

        // cache
        ->set('.json_encoder.cache_warmer.encoder_decoder', EncoderDecoderCacheWarmer::class)
            ->args([
                abstract_arg('encodable class names'),
                service('json_encoder.encode.property_metadata_loader'),
                service('json_encoder.decode.property_metadata_loader'),
                param('.json_encoder.encoders_dir'),
                param('.json_encoder.decoders_dir'),
                service('logger')->ignoreOnInvalid(),
            ])
            ->tag('kernel.cache_warmer')

        ->set('.json_encoder.cache_warmer.lazy_ghost', LazyGhostCacheWarmer::class)
            ->args([
                abstract_arg('encodable class names'),
                param('.json_encoder.lazy_ghosts_dir'),
            ])
            ->tag('kernel.cache_warmer')
    ;
};
