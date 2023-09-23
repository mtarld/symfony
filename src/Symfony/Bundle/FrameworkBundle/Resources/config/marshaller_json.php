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

use Symfony\Component\JsonMarshaller\CacheWarmer\TemplateCacheWarmer;
use Symfony\Component\JsonMarshaller\JsonMarshaller;
use Symfony\Component\JsonMarshaller\JsonUnmarshaller;
use Symfony\Component\JsonMarshaller\Marshal\Template\Template as MarshalTemplate;
use Symfony\Component\JsonMarshaller\Unmarshal\Template\Template as UnmarshalTemplate;

return static function (ContainerConfigurator $container) {
    $container->parameters()
        ->set('.marshaller.json.cache_dir.template', '%kernel.cache_dir%/marshaller_json/template')
    ;

    $container->services()
        // Marshaller/Unmarshaller
        ->set('marshaller.json.marshaller', JsonMarshaller::class)
            ->args([
                service('.marshaller.json.marshal.template'),
                param('.marshaller.json.cache_dir.template'),
                abstract_arg('marshal runtime services'),
            ])

        ->alias(JsonMarshaller::class, 'marshaller.json.marshaller')

        ->set('marshaller.json.unmarshaller.eager', JsonUnmarshaller::class)
            ->args([
                service('.marshaller.json.unmarshal.template'),
                service('marshaller.instantiator.eager'),
                param('.marshaller.json.cache_dir.template'),
                abstract_arg('unmarshal runtime services'),
                false,
            ])
        ->set('marshaller.json.unmarshaller.lazy', JsonUnmarshaller::class)
            ->args([
                service('.marshaller.json.unmarshal.template'),
                service('marshaller.instantiator.lazy'),
                param('.marshaller.json.cache_dir.template'),
                abstract_arg('unmarshal runtime services'),
                true,
            ])

        // Template
        ->set('.marshaller.json.marshal.template', MarshalTemplate::class)
            ->args([
                service('.marshaller.marshal.data_model_builder'),
                param('.marshaller.json.cache_dir.template'),
            ])

        ->set('.marshaller.json.unmarshal.template', UnmarshalTemplate::class)
            ->args([
                service('.marshaller.unmarshal.data_model_builder'),
                param('.marshaller.json.cache_dir.template'),
            ])

        // Cache
        ->set('.marshaller.json.cache_warmer.template', TemplateCacheWarmer::class)
            ->args([
                abstract_arg('marshallable types'),
                service('.marshaller.json.marshal.template'),
                service('.marshaller.json.unmarshal.template'),
                param('.marshaller.json.cache_dir.template'),
                service('logger')->ignoreOnInvalid(),
            ])
            ->tag('kernel.cache_warmer')
    ;
};
