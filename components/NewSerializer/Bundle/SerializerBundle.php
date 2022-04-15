<?php

declare(strict_types=1);

namespace Symfony\Component\NewSerializer\Bundle;

use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\NewSerializer\Encoder\JsonEncoderFactory;
use Symfony\Component\NewSerializer\Extractor\ObjectPropertyListExtractor;
use Symfony\Component\NewSerializer\Extractor\ObjectPropertyListExtractorInterface;
use Symfony\Component\NewSerializer\Marshaller;
use Symfony\Component\NewSerializer\MarshallerInterface;
use Symfony\Component\NewSerializer\Marshalling\MarshallerFactory;
use Symfony\Component\NewSerializer\Marshalling\Strategy\DictMarshallingStrategy;
use Symfony\Component\NewSerializer\Marshalling\Strategy\ListMarshallingStrategy;
use Symfony\Component\NewSerializer\Marshalling\Strategy\MarshallableMarshallingStrategy;
use Symfony\Component\NewSerializer\Marshalling\Strategy\MarshallingStrategyInterface;
use Symfony\Component\NewSerializer\Marshalling\Strategy\ObjectMarshallingStrategy;
use Symfony\Component\NewSerializer\Marshalling\Strategy\ScalarMarshallingStrategy;

// TODO name it marshaller instead
final class SerializerBundle extends Bundle
{
    // TODO see what should be internal
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Encoders
        $container->register('marshaller.encoder.factory.json', JsonEncoderFactory::class);

        // Mashaller strategies
        $container->registerForAutoconfiguration(MarshallingStrategyInterface::class)
            ->addTag('marshaller.marshalling_strategy');

        $container->register('marshaller.marshalling_strategy.dict', DictMarshallingStrategy::class)
            ->setAutoconfigured(false)
            ->addTag('marshaller.marshalling_strategy', ['priority' => -256]);

        $container->register('marshaller.marshalling_strategy.list', ListMarshallingStrategy::class)
            ->setAutoconfigured(false)
            ->addTag('marshaller.marshalling_strategy', ['priority' => -256]);

        $container->register('marshaller.marshalling_strategy.mashallable', MarshallableMarshallingStrategy::class)
            ->setAutoconfigured(false)
            ->addTag('marshaller.marshalling_strategy', ['priority' => -128]);

        $container->register('marshaller.marshalling_strategy.object', ObjectMarshallingStrategy::class)
            ->setArguments([
new Reference('serializer.extractor.object_property_list'),
new Reference('property_accessor'),
            ])
            ->setAutoconfigured(false)
            ->addTag('marshaller.marshalling_strategy', ['priority' => -256]);

        $container->register('marshaller.marshalling_strategy.scalar', ScalarMarshallingStrategy::class)
            ->setAutoconfigured(false)
            ->addTag('marshaller.marshalling_strategy', ['priority' => -256]);

        // Extractors
        $container->register('serializer.extractor.object_property_list', ObjectPropertyListExtractor::class)
        ->setArguments([
            new Reference('property_info'),
        ]);
        $container->setAlias(ObjectPropertyListExtractorInterface::class, 'serializer.extractor.object_property_list');

        // Marshaller
        $container->register('marshaller.marshalling.factory.json', MarshallerFactory::class)
            ->setArguments([
                new TaggedIteratorArgument('marshaller.marshalling_strategy'),
                new Reference('marshaller.encoder.factory.json'),
            ]);

        $container->register('marshaller.json', Marshaller::class)
            ->setArguments([
                new Reference('marshaller.marshalling.factory.json'),
            ]);

        $container->registerAliasForArgument('marshaller.json', MarshallerInterface::class, 'jsonMarshaller');

        $container->setAlias(MarshallerInterface::class, 'serializer');
    }
}
