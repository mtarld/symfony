<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Bundle;

use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Marshaller\Encoder\JsonEncoderFactory;
use Symfony\Component\Marshaller\Extractor\ObjectPropertyListExtractor;
use Symfony\Component\Marshaller\Extractor\ObjectPropertyListExtractorInterface;
use Symfony\Component\Marshaller\Marshaller;
use Symfony\Component\Marshaller\MarshallerInterface;
use Symfony\Component\Marshaller\Marshalling\Strategy\DictMarshallingStrategy;
use Symfony\Component\Marshaller\Marshalling\Strategy\ListMarshallingStrategy;
use Symfony\Component\Marshaller\Marshalling\Strategy\MarshallableMarshallingStrategy;
use Symfony\Component\Marshaller\Marshalling\Strategy\MarshallingStrategyInterface;
use Symfony\Component\Marshaller\Marshalling\Strategy\ObjectMarshallingStrategy;
use Symfony\Component\Marshaller\Marshalling\Strategy\ScalarMarshallingStrategy;

final class MarshallerBundle extends Bundle
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
new Reference('marshaller.extractor.object_property_list'),
new Reference('property_accessor'),
            ])
            ->setAutoconfigured(false)
            ->addTag('marshaller.marshalling_strategy', ['priority' => -256]);

        $container->register('marshaller.marshalling_strategy.scalar', ScalarMarshallingStrategy::class)
            ->setAutoconfigured(false)
            ->addTag('marshaller.marshalling_strategy', ['priority' => -256]);

        // Extractors
        $container->register('marshaller.extractor.object_property_list', ObjectPropertyListExtractor::class)
        ->setArguments([
            new Reference('property_info'),
        ]);
        $container->setAlias(ObjectPropertyListExtractorInterface::class, 'marshaller.extractor.object_property_list');

        // Marshaller
        $container->register('marshaller.marshaller.json', Marshaller::class)
            ->setArguments([
                new TaggedIteratorArgument('marshaller.marshalling_strategy'),
                new Reference('marshaller.encoder.factory.json'),
            ]);

        $container->registerAliasForArgument('marshaller.marshaller.json', MarshallerInterface::class, 'jsonMarshaller');
        $container->setAlias(MarshallerInterface::class, 'marshaller.marshaller.json');
    }
}
