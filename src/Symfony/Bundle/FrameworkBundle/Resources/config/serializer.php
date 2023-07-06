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

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\CacheWarmer\SerializerCacheWarmer;
use Symfony\Bundle\FrameworkBundle\CacheWarmer\SerializerDeserializerCacheWarmer;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\ErrorHandler\ErrorRenderer\SerializerErrorRenderer;
use Symfony\Component\PropertyInfo\Extractor\SerializerExtractor;
use Symfony\Component\Serializer\Deserialize\Decoder\CsvDecoder;
use Symfony\Component\Serializer\Deserialize\Decoder\JsonDecoder;
use Symfony\Component\Serializer\Deserialize\Instantiator\EagerInstantiator;
use Symfony\Component\Serializer\Deserialize\Instantiator\InstantiatorInterface;
use Symfony\Component\Serializer\Deserialize\Instantiator\LazyInstantiator;
use Symfony\Component\Serializer\Deserialize\Mapping\PropertyMetadataLoader;
use Symfony\Component\Serializer\Deserialize\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\Serializer\Deserialize\Splitter\JsonDictSplitter;
use Symfony\Component\Serializer\Deserialize\Splitter\JsonListSplitter;
use Symfony\Component\Serializer\Deserialize\Unmarshaller\EagerUnmarshaller;
use Symfony\Component\Serializer\Deserialize\Unmarshaller\LazyUnmarshaller;
use Symfony\Component\Serializer\Deserialize\Deserializer;
use Symfony\Component\Serializer\Deserialize\DeserializerInterface;
use Symfony\Component\Serializer\Serialize\Encoder\CsvEncoder as ExperimentalCsvEncoder;
use Symfony\Component\Serializer\Serialize\PropertyConfigurator\SerializePropertyConfigurator;
use Symfony\Component\Serializer\Serialize\PropertyConfigurator\SerializePropertyConfiguratorInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Serialize\Dom\DomTreeBuilder;
use Symfony\Component\Serializer\Serialize\Dom\DomTreeBuilderInterface;
use Symfony\Component\Serializer\Serialize\Template\JsonTemplateGenerator;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Mapping\Factory\CacheClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Mapping\Loader\LoaderChain;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\ConstraintViolationListNormalizer;
use Symfony\Component\Serializer\Normalizer\DataUriNormalizer;
use Symfony\Component\Serializer\Normalizer\DateIntervalNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\FormErrorNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\MimeMessageNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ProblemNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Normalizer\UnwrappingDenormalizer;
use Symfony\Component\Serializer\SerializableResolver\CachedSerializableResolver;
use Symfony\Component\Serializer\SerializableResolver\PathSerializableResolver;
use Symfony\Component\Serializer\SerializableResolver\SerializableResolverInterface;
use Symfony\Component\Serializer\Serialize\Serializer as ExperimentalSerializer;
use Symfony\Component\Serializer\Serialize\SerializerInterface as ExperimentalSerializerInterface;
use Symfony\Component\Serializer\Serialize\Template\NormalizerEncoderTemplateGenerator;
use Symfony\Component\Serializer\Serialize\Template\TemplateFactory;
use Symfony\Component\Serializer\Serialize\Template\TemplateVariationExtractor;
use Symfony\Component\Serializer\Serialize\Template\TemplateVariationExtractorInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Type\PhpstanTypeExtractor;
use Symfony\Component\Serializer\Type\ReflectionTypeExtractor;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;

return static function (ContainerConfigurator $container) {
    $container->parameters()
        ->set('serializer.mapping.cache.file', '%kernel.cache_dir%/serialization.php')
    ;

    $container->services()
        ->set('serializer', Serializer::class)
            ->args([[], []])

        ->alias(SerializerInterface::class, 'serializer')
        ->alias(NormalizerInterface::class, 'serializer')
        ->alias(DenormalizerInterface::class, 'serializer')
        ->alias(EncoderInterface::class, 'serializer')
        ->alias(DecoderInterface::class, 'serializer')

        ->alias('serializer.property_accessor', 'property_accessor')

        // Discriminator Map
        ->set('serializer.mapping.class_discriminator_resolver', ClassDiscriminatorFromClassMetadata::class)
            ->args([service('serializer.mapping.class_metadata_factory')])

        ->alias(ClassDiscriminatorResolverInterface::class, 'serializer.mapping.class_discriminator_resolver')

        // Normalizer
        ->set('serializer.normalizer.constraint_violation_list', ConstraintViolationListNormalizer::class)
            ->args([1 => service('serializer.name_converter.metadata_aware')])
            ->autowire(true)
            ->tag('serializer.normalizer', ['priority' => -915])

        ->set('serializer.normalizer.mime_message', MimeMessageNormalizer::class)
            ->args([service('serializer.normalizer.property')])
            ->tag('serializer.normalizer', ['priority' => -915])

        ->set('serializer.normalizer.datetimezone', DateTimeZoneNormalizer::class)
            ->tag('serializer.normalizer', ['priority' => -915])

        ->set('serializer.normalizer.dateinterval', DateIntervalNormalizer::class)
            ->tag('serializer.normalizer', ['priority' => -915])

        ->set('serializer.normalizer.data_uri', DataUriNormalizer::class)
            ->args([service('mime_types')->nullOnInvalid()])
            ->tag('serializer.normalizer', ['priority' => -920])

        ->set('serializer.normalizer.datetime', DateTimeNormalizer::class)
            ->tag('serializer.normalizer', ['priority' => -910])

        ->set('serializer.normalizer.json_serializable', JsonSerializableNormalizer::class)
            ->args([null, null])
            ->tag('serializer.normalizer', ['priority' => -950])

        ->set('serializer.normalizer.problem', ProblemNormalizer::class)
            ->args([param('kernel.debug'), '$translator' => service('translator')->nullOnInvalid()])
            ->tag('serializer.normalizer', ['priority' => -890])

        ->set('serializer.denormalizer.unwrapping', UnwrappingDenormalizer::class)
            ->args([service('serializer.property_accessor')])
            ->tag('serializer.normalizer', ['priority' => 1000])

        ->set('serializer.normalizer.uid', UidNormalizer::class)
            ->tag('serializer.normalizer', ['priority' => -890])

        ->set('serializer.normalizer.form_error', FormErrorNormalizer::class)
            ->tag('serializer.normalizer', ['priority' => -915])

        ->set('serializer.normalizer.object', ObjectNormalizer::class)
            ->args([
                service('serializer.mapping.class_metadata_factory'),
                service('serializer.name_converter.metadata_aware'),
                service('serializer.property_accessor'),
                service('property_info')->ignoreOnInvalid(),
                service('serializer.mapping.class_discriminator_resolver')->ignoreOnInvalid(),
                null,
            ])
            ->tag('serializer.normalizer', ['priority' => -1000])

        ->alias(ObjectNormalizer::class, 'serializer.normalizer.object')
            ->deprecate('symfony/serializer', '6.2', 'The "%alias_id%" service alias is deprecated, type-hint against "'.NormalizerInterface::class.'" or implement "'.NormalizerAwareInterface::class.'" instead.')

        ->set('serializer.normalizer.property', PropertyNormalizer::class)
            ->args([
                service('serializer.mapping.class_metadata_factory'),
                service('serializer.name_converter.metadata_aware'),
                service('property_info')->ignoreOnInvalid(),
                service('serializer.mapping.class_discriminator_resolver')->ignoreOnInvalid(),
                null,
            ])

        ->alias(PropertyNormalizer::class, 'serializer.normalizer.property')
            ->deprecate('symfony/serializer', '6.2', 'The "%alias_id%" service alias is deprecated, type-hint against "'.NormalizerInterface::class.'" or implement "'.NormalizerAwareInterface::class.'" instead.')

        ->set('serializer.denormalizer.array', ArrayDenormalizer::class)
            ->tag('serializer.normalizer', ['priority' => -990])

        // Loader
        ->set('serializer.mapping.chain_loader', LoaderChain::class)
            ->args([[]])

        // Class Metadata Factory
        ->set('serializer.mapping.class_metadata_factory', ClassMetadataFactory::class)
            ->args([service('serializer.mapping.chain_loader')])

        ->alias(ClassMetadataFactoryInterface::class, 'serializer.mapping.class_metadata_factory')

        // Cache
        ->set('serializer.mapping.cache_warmer', SerializerCacheWarmer::class)
            ->args([abstract_arg('The serializer metadata loaders'), param('serializer.mapping.cache.file')])
            ->tag('kernel.cache_warmer')

        ->set('serializer.mapping.cache.symfony', CacheItemPoolInterface::class)
            ->factory([PhpArrayAdapter::class, 'create'])
            ->args([param('serializer.mapping.cache.file'), service('cache.serializer')])

        ->set('serializer.mapping.cache_class_metadata_factory', CacheClassMetadataFactory::class)
            ->decorate('serializer.mapping.class_metadata_factory')
            ->args([
                service('serializer.mapping.cache_class_metadata_factory.inner'),
                service('serializer.mapping.cache.symfony'),
            ])

        // Encoders
        ->set('serializer.encoder.xml', XmlEncoder::class)
            ->tag('serializer.encoder')

        ->set('serializer.encoder.json', JsonEncoder::class)
            ->args([null, null])
            ->tag('serializer.encoder')

        ->set('serializer.encoder.yaml', YamlEncoder::class)
            ->args([null, null])
            ->tag('serializer.encoder')

        ->set('serializer.encoder.csv', CsvEncoder::class)
            ->tag('serializer.encoder')

        // Name converter
        ->set('serializer.name_converter.camel_case_to_snake_case', CamelCaseToSnakeCaseNameConverter::class)

        ->set('serializer.name_converter.metadata_aware', MetadataAwareNameConverter::class)
            ->args([service('serializer.mapping.class_metadata_factory')])

        // PropertyInfo extractor
        ->set('property_info.serializer_extractor', SerializerExtractor::class)
            ->args([service('serializer.mapping.class_metadata_factory')])
            ->tag('property_info.list_extractor', ['priority' => -999])

        // ErrorRenderer integration
        ->alias('error_renderer', 'error_renderer.serializer')
        ->alias('error_renderer.serializer', 'error_handler.error_renderer.serializer')

        ->set('error_handler.error_renderer.serializer', SerializerErrorRenderer::class)
            ->args([
                service('serializer'),
                inline_service()
                    ->factory([SerializerErrorRenderer::class, 'getPreferredFormat'])
                    ->args([service('request_stack')]),
                service('error_renderer.html'),
                inline_service()
                    ->factory([HtmlErrorRenderer::class, 'isDebug'])
                    ->args([service('request_stack'), param('kernel.debug')]),
            ])
    ;

    if (interface_exists(\BackedEnum::class)) {
        $container->services()
            ->set('serializer.normalizer.backed_enum', BackedEnumNormalizer::class)
            ->tag('serializer.normalizer', ['priority' => -915])
        ;
    }

    //
    // Experimental serializer
    //

    $container->parameters()
        ->set('.serializer.cache_dir.template', '%kernel.cache_dir%/serializer/template')
        ->set('.serializer.cache_dir.lazy_object', '%kernel.cache_dir%/serializer/lazy_object')
    ;

    $container->services()
        // Serializer
        ->set('serializer.serializer', ExperimentalSerializer::class)
            ->args([
                service('serializer.template_factory'),
                param('.serializer.cache_dir.template'),
            ])
        ->alias(ExperimentalSerializerInterface::class, 'serializer.serializer')

        // Template
        ->set('serializer.template_factory', TemplateFactory::class)
            ->args([
                service('serializer.dom_tree_builder'),
                service('serializer.template_variation_extractor'),
                abstract_arg('template generators'),
                param('.serializer.cache_dir.template'),
            ])

        ->set('serializer.template_variation_extractor', TemplateVariationExtractor::class)
        ->alias(TemplateVariationExtractorInterface::class, 'serializer.template_variation_extractor')

        // Template generators
        ->set('serializer.template_generator.json', JsonTemplateGenerator::class)
            ->args([
                service('serializer.type_extractor'),
            ])
            ->tag('serializer.template_generator', ['format' => 'json'])

        ->set('serializer.template_generator.csv', NormalizerEncoderTemplateGenerator::class)
            ->args([
                service('serializer.type_extractor'),
                ExperimentalCsvEncoder::class,
            ])
            ->tag('serializer.template_generator', ['format' => 'csv'])

        // DOM tree builders
        ->set('serializer.dom_tree_builder', DomTreeBuilder::class)
            ->args([
                service('serializer.type_extractor'),
                service('serializer.serialize.property_configurator'),
            ])
        ->alias(DomTreeBuilderInterface::class, 'serializer.dom_tree_builder')

        // Deserializer
        ->set('serializer.deserializer', Deserializer::class)
            ->args([
                abstract_arg('eager unmarshallers'),
                abstract_arg('lazy unmarshallers'),
            ])
        ->alias(DeserializerInterface::class, 'serializer.deserializer')

        // Unmarshallers
        ->set('serializer.unmarshaller.json.eager', EagerUnmarshaller::class)
            ->args([
                inline_service(JsonDecoder::class),
                service('serializer.deserialize.metadata.property_loader'),
                service('serializer.instantiator'),
            ])
            ->tag('serializer.unmarshaller.eager', ['format' => 'json'])

        ->set('serializer.unmarshaller.json.lazy', LazyUnmarshaller::class)
            ->args([
                inline_service(JsonDecoder::class),
                inline_service(JsonListSplitter::class),
                inline_service(JsonDictSplitter::class),
                service('serializer.deserialize.metadata.property_loader'),
                service('serializer.instantiator'),
            ])
            ->tag('serializer.unmarshaller.lazy', ['format' => 'json'])

        ->set('serializer.unmarshaller.csv.eager', EagerUnmarshaller::class)
            ->args([
                inline_service(CsvDecoder::class)
                    ->args([
                        service('serializer.encoder.csv'),
                    ]),
                service('serializer.deserialize.metadata.property_loader'),
                service('serializer.instantiator'),
            ])
            ->tag('serializer.unmarshaller.eager', ['format' => 'csv'])

        // Instantiators
        ->set('serializer.instantiator.eager', EagerInstantiator::class)

        ->set('serializer.instantiator.lazy', LazyInstantiator::class)
            ->args([
                param('.serializer.cache_dir.lazy_object'),
            ])

        ->alias('serializer.instantiator', 'serializer.instantiator.eager')
        ->alias(InstantiatorInterface::class, 'serializer.instantiator')

        // Property configurators
        ->set('serializer.serialize.property_configurator', SerializePropertyConfigurator::class)
            ->args([
                service('serializer.type_extractor'),
            ])
        ->alias(SerializePropertyConfiguratorInterface::class, 'serializer.serialize.property_configurator')

        // Metadata
        ->set('serializer.deserialize.metadata.property_loader', PropertyMetadataLoader::class)
            ->args([
                service('serializer.type_extractor'),
            ])
        ->alias(PropertyMetadataLoaderInterface::class, 'serializer.deserialize.metadata.property_loader')

        // Type extractors
        ->set('serializer.type_extractor.reflection', ReflectionTypeExtractor::class)
            ->lazy()
            ->tag('proxy', ['interface' => TypeExtractorInterface::class])

        ->set('serializer.type_extractor.phpstan', PhpstanTypeExtractor::class)
            ->decorate('serializer.type_extractor.reflection')
            ->args([
                service('serializer.type_extractor.phpstan.inner'),
            ])
            ->lazy()
            ->tag('proxy', ['interface' => TypeExtractorInterface::class])
        ->alias('serializer.type_extractor', 'serializer.type_extractor.reflection')
        ->alias(TypeExtractorInterface::class, 'serializer.type_extractor')

        // Serializable resolvers
        ->set('serializer.serializable_resolver', PathSerializableResolver::class)
            ->args([
                param('serializer.serializable_paths'),
            ])

        ->set('serializer.serializable_resolver.cached', CachedSerializableResolver::class)
            ->decorate('serializer.serializable_resolver', priority: -1024)
            ->args([
                service('serializer.serializable_resolver.cached.inner'),
                service('cache.serializer')->ignoreOnInvalid(),
            ])
        ->alias(SerializableResolverInterface::class, 'serializer.serializable_resolver')

        // Cache
        ->set('serializer.cache_warmer', SerializerDeserializerCacheWarmer::class)
            ->args([
                service('serializer.serializable_resolver'),
                service('serializer.template_factory'),
                service('serializer.template_variation_extractor'),
                param('.serializer.cache_dir.template'),
                param('.serializer.cache_dir.lazy_object'),
                param('serializer.template_warm_up.formats'),
                param('serializer.template_warm_up.max_variants'),
                service('logger')->ignoreOnInvalid(),
            ])
            ->tag('kernel.cache_warmer')
    ;
};
