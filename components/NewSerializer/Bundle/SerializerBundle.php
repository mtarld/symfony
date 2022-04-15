<?php

namespace Symfony\Component\NewSerializer\Bundle;

use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\NewSerializer\Encoder\EncoderFactory;
use Symfony\Component\NewSerializer\Encoder\EncoderFactoryInterface;
use Symfony\Component\NewSerializer\Encoder\JsonEncoderFactory;
use Symfony\Component\NewSerializer\Extractor\ObjectPropertyListExtractor;
use Symfony\Component\NewSerializer\Extractor\ObjectPropertyListExtractorInterface;
use Symfony\Component\NewSerializer\Serializer;
use Symfony\Component\NewSerializer\Serializer\ChainSerializer;
use Symfony\Component\NewSerializer\Serializer\DictSerializer;
use Symfony\Component\NewSerializer\Serializer\ListSerializer;
use Symfony\Component\NewSerializer\Serializer\ObjectSerializer;
use Symfony\Component\NewSerializer\Serializer\ScalarSerializer;
use Symfony\Component\NewSerializer\Serializer\SerializableSerializer;
use Symfony\Component\NewSerializer\SerializerInterface;
use Symfony\Component\NewSerializer\Serializer\SerializerInterface as SymfonySerializerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;

final class SerializerBundle extends Bundle
{
    // TODO see what should be internal
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Encoders
        $container->registerForAutoconfiguration(EncoderFactoryInterface::class)
            ->addTag('serializer.encoder.factory', ['priority' => -128]);

        $container->register('serializer.encoder.factory.json', JsonEncoderFactory::class)
            ->setAutoconfigured(false)
            ->addTag('serializer.encoder.factory');

        $container->register('serializer.encoder.factory', EncoderFactory::class)
            ->setArguments([
                new TaggedIteratorArgument('serializer.encoder.factory'),
            ]);

        // Serializers
        $container->registerForAutoconfiguration(SymfonySerializerInterface::class)
            ->addTag('serializer.encoder.factory');

        $container->register('serializer.serializer.chain', ChainSerializer::class);

        $container->register('serializer.serializer.dict', DictSerializer::class)
            ->setAutoconfigured(false)
            ->addTag('serializer.serializer', ['priority' => -256]);

        $container->register('serializer.serializer.list', ListSerializer::class)
            ->setAutoconfigured(false)
            ->addTag('serializer.serializer', ['priority' => -256]);

        $container->register('serializer.serializer.object', ObjectSerializer::class)
            ->setArguments([
                new Reference('serializer.extractor.object_property_list'),
                new Reference('property_accessor'),
            ])
            ->setAutoconfigured(false)
            ->addTag('serializer.serializer', ['priority' => -256]);

        $container->register('serializer.serializer.scalar', ScalarSerializer::class)
            ->setAutoconfigured(false)
            ->addTag('serializer.serializer', ['priority' => -256]);

        $container->register('serializer.serializer.serializable', SerializableSerializer::class)
            ->setAutoconfigured(false)
            ->addTag('serializer.serializer', ['priority' => -128]);

        // Extractor
        $container->register('serializer.extractor.object_property_list', ObjectPropertyListExtractor::class)
        ->setArguments([
            new Reference('property_info'),
        ]);
        $container->setAlias(ObjectPropertyListExtractorInterface::class, 'serializer.extractor.object_property_list');

        // Main serializer
        $container->register('serializer', Serializer::class)
            ->setArguments([
                new TaggedIteratorArgument('serializer.serializer'),
                new Reference('serializer.encoder.factory'),
            ]);

        $container->setAlias(SerializerInterface::class, 'serializer');
    }
}

