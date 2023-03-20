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

use Symfony\Component\Json\CacheWarmer\TemplateCacheWarmer;
use Symfony\Component\Json\JsonDecoder;
use Symfony\Component\Json\JsonEncoder;
use Symfony\Component\Json\JsonStreamingDecoder;
use Symfony\Component\Json\JsonStreamingEncoder;
use Symfony\Component\Json\Template\Decode\Template as DecodeTemplate;
use Symfony\Component\Json\Template\Encode\Template as EncodeTemplate;

return static function (ContainerConfigurator $container) {
    $container->parameters()
        ->set('.json.cache_dir.template', '%kernel.cache_dir%/encoder_json/template')
    ;

    $container->services()
        // Encoder/Decoder
        ->set('json.encoder', JsonEncoder::class)
            ->args([
                service('.json.encode.template'),
                param('.json.cache_dir.template'),
                service('.encoder.runtime_services'),
            ])
        ->set('json.encoder.stream', JsonStreamingEncoder::class)
            ->args([
                service('.json.encode.template'),
                param('.json.cache_dir.template'),
                service('.encoder.runtime_services'),
            ])
        ->set('json.decoder', JsonDecoder::class)
            ->args([
                service('.json.decode.template'),
                service('encoder.instantiator.eager'),
                param('.json.cache_dir.template'),
                service('.encoder.runtime_services'),
            ])
        ->set('json.decoder.stream', JsonStreamingDecoder::class)
            ->args([
                service('.json.decode.template'),
                service('encoder.instantiator.lazy'),
                param('.json.cache_dir.template'),
                service('.encoder.runtime_services'),
            ])

        ->alias(JsonEncoder::class, 'json.encoder')
        ->alias(JsonStreamingEncoder::class, 'json.encoder.stream')
        ->alias(JsonDecoder::class, 'json.decoder')
        ->alias(JsonStreamingDecoder::class, 'json.decoder.stream')

        // Template
        ->set('.json.encode.template', EncodeTemplate::class)
            ->args([
                service('.encoder.encode.data_model_builder'),
                param('.json.cache_dir.template'),
            ])

        ->set('.json.decode.template', DecodeTemplate::class)
            ->args([
                service('.encoder.decode.data_model_builder'),
                param('.json.cache_dir.template'),
            ])

        // Cache
        ->set('.json.cache_warmer.template', TemplateCacheWarmer::class)
            ->args([
                abstract_arg('encodable types'),
                service('.json.encode.template'),
                service('.json.decode.template'),
                param('.json.cache_dir.template'),
                service('logger')->ignoreOnInvalid(),
            ])
            ->tag('kernel.cache_warmer')
    ;
};
