<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Bundle;

use PhpParser\PrettyPrinter\Standard;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Marshaller\Cache\TemplateCacheWarmer;
use Symfony\Component\Marshaller\Cache\WarmableResolver;
use Symfony\Component\Marshaller\Context\ContextDeclinationResolver;
use Symfony\Component\Marshaller\Context\Option\GroupsOptionDeclination;
use Symfony\Component\Marshaller\Marshaller;
use Symfony\Component\Marshaller\MarshallerInterface;
use Symfony\Component\Marshaller\Metadata\Attribute\PropertyAttributeResolver;
use Symfony\Component\Marshaller\Metadata\ClassMetadataFactory;
use Symfony\Component\Marshaller\Metadata\Filterer\GroupsPropertyFilterer;
use Symfony\Component\Marshaller\Metadata\Filterer\NoneValuePropertyFilterer;
use Symfony\Component\Marshaller\Metadata\Filterer\NoOpPropertyFilterer;
use Symfony\Component\Marshaller\Metadata\NameConverter\NameAttributePropertyNameConverter;
use Symfony\Component\Marshaller\Metadata\NameConverter\NoOpPropertyNameConverter;
use Symfony\Component\Marshaller\Metadata\PropertyMetadataFactory;
use Symfony\Component\Marshaller\Metadata\Type\MethodReturnTypeExtractor;
use Symfony\Component\Marshaller\Metadata\Type\PropertyTypeExtractor;
use Symfony\Component\Marshaller\Metadata\Type\TypeFactory;
use Symfony\Component\Marshaller\Metadata\ValueMetadataFactory;
use Symfony\Component\Marshaller\Template\Generator\Generator;
use Symfony\Component\Marshaller\Template\Generator\Json\JsonDictValueGenerator;
use Symfony\Component\Marshaller\Template\Generator\Json\JsonListValueGenerator;
use Symfony\Component\Marshaller\Template\Generator\Json\JsonObjectValueGenerator;
use Symfony\Component\Marshaller\Template\Generator\Json\JsonScalarValueGenerator;
use Symfony\Component\Marshaller\Template\Generator\Json\JsonStructureGenerator;
use Symfony\Component\Marshaller\Template\Generator\Memory\MemoryDictValueGenerator;
use Symfony\Component\Marshaller\Template\Generator\Memory\MemoryListValueGenerator;
use Symfony\Component\Marshaller\Template\Generator\Memory\MemoryObjectValueGenerator;
use Symfony\Component\Marshaller\Template\Generator\Memory\MemoryScalarValueGenerator;
use Symfony\Component\Marshaller\Template\Generator\Memory\MemoryStructureGenerator;
use Symfony\Component\Marshaller\Template\Generator\ValueGenerators;
use Symfony\Component\Marshaller\Template\TemplateFilenameBuilder;
use Symfony\Component\Marshaller\Template\TemplateLoader;

final class MarshallerBundle extends Bundle
{
    // TODO see what should be internal
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->setParameter('marshaller.marshallable_paths', [
            sprintf('src/Dto', $container->getParameter('kernel.project_dir')),
        ]);

        $container->setParameter('marshaller.max_declination', 16);

        $container->setParameter('marshaller.depth.max_depth', 1);
        // $container->setParameter('marshaller.depth.max_depth', 8);
        $container->setParameter('marshaller.depth.reject_circular_reference', false);

        // Marshaller
        $container->register('marshaller', Marshaller::class)
            ->setArguments([
                // new Reference('marshaller.template.loader'),
            ]);

        $container->setAlias(MarshallerInterface::class, 'marshaller');

        // Property filterers
        $container->register('marshaller.metadata.property_filterer.no_op', NoOpPropertyFilterer::class);

        $container->register('marshaller.metadata.property_filterer.no_value', NoneValuePropertyFilterer::class)
            ->setDecoratedService('marshaller.metadata.property_filterer.no_op')
            ->setArguments([
                new Reference('.inner'),
            ]);

        $container->register('marshaller.metadata.property_filterer.groups', GroupsPropertyFilterer::class)
            ->setDecoratedService('marshaller.metadata.property_filterer.no_value')
            ->setArguments([
                new Reference('.inner'),
            ]);

        $container->setAlias('marshaller.metadata.property_filterer', 'marshaller.metadata.property_filterer.no_op');

        // Name converters
        $container->register('marshaller.metadata.property_name_converter.no_op', NoOpPropertyNameConverter::class);

        $container->register('marshaller.metadata.property_name_converter.marshalled_name', NameAttributePropertyNameConverter::class)
            ->setDecoratedService('marshaller.metadata.property_name_converter.no_op')
            ->setArguments([
                new Reference('.inner'),
            ]);

        $container->setAlias('marshaller.metadata.property_name_converter', 'marshaller.metadata.property_name_converter.no_op');

        // Metadata
        $container->register('marshaller.metadata.class_factory', ClassMetadataFactory::class)
            ->setArguments([
                new Reference('marshaller.metadata.property_factory'),
                new Reference('marshaller.metadata.property_filterer'),
            ]);

        $container->register('marshaller.metadata.property_factory', PropertyMetadataFactory::class)
            ->setArguments([
                new Reference('marshaller.metadata.value_factory'),
                new Reference('marshaller.metadata.property_attribute_resolver'),
                new Reference('marshaller.metadata.property_name_converter'),
            ]);

        $container->register('marshaller.metadata.value_factory', ValueMetadataFactory::class)
            ->setArguments([
                new ServiceLocatorArgument([
                    ClassMetadataFactory::class => new Reference('marshaller.metadata.class_factory'),
                ]),
                new Reference('marshaller.metadata.property_type_extractor'),
                new Reference('marshaller.metadata.method_return_type_extractor'),
                new Parameter('marshaller.depth.max_depth'),
                new Parameter('marshaller.depth.reject_circular_reference'),
            ]);

        $container->register('marshaller.metadata.property_attribute_resolver', PropertyAttributeResolver::class);

        // Type extractors
        $container->register('marshaller.metadata.type_factory', TypeFactory::class);

        $container->register('marshaller.metadata.property_type_extractor', PropertyTypeExtractor::class)
            ->setArguments([
                new Reference('property_info'),
                new Reference('marshaller.metadata.type_factory'),
            ]);

        $container->register('marshaller.metadata.method_return_type_extractor', MethodReturnTypeExtractor::class)
            ->setArguments([
                new Reference('marshaller.metadata.type_factory'),
            ]);

        // Json generators
        $container->register('marshaller.template.structure_generator.json', JsonStructureGenerator::class);

        $container->register('marshaller.template.scalar_value_generator.json', JsonScalarValueGenerator::class)
            ->addTag('marshaller.template.value_generator.json');

        $container->register('marshaller.template.list_value_generator.json', JsonListValueGenerator::class)
            ->setArguments([
                new Reference('marshaller.template.value_generators.json'),
            ])
            ->addTag('marshaller.template.value_generator.json');

        $container->register('marshaller.template.dict_value_generator.json', JsonDictValueGenerator::class)
            ->setArguments([
                new Reference('marshaller.template.value_generators.json'),
            ])
            ->addTag('marshaller.template.value_generator.json');

        $container->register('marshaller.template.object_value_generator.json', JsonObjectValueGenerator::class)
            ->setArguments([
                new Reference('marshaller.template.value_generators.json'),
            ])
            ->addTag('marshaller.template.value_generator.json');

        $container->register('marshaller.template.value_generators.json', ValueGenerators::class)
            ->setArguments([
                new TaggedIteratorArgument('marshaller.template.value_generator.json'),
            ]);

        $container->register('marshaller.template.generator.json', Generator::class)
            ->setArguments([
                new Reference('marshaller.template.structure_generator.json'),
                new Reference('marshaller.template.value_generators.json'),
            ]);

        // Memory generators
        $container->register('marshaller.template.structure_generator.memory', MemoryStructureGenerator::class);

        $container->register('marshaller.template.scalar_value_generator.memory', MemoryScalarValueGenerator::class)
            ->addTag('marshaller.template.value_generator.memory');

        $container->register('marshaller.template.list_value_generator.memory', MemoryListValueGenerator::class)
            ->setArguments([
                new Reference('marshaller.template.value_generators.memory'),
            ])
            ->addTag('marshaller.template.value_generator.memory');

        $container->register('marshaller.template.dict_value_generator.memory', MemoryDictValueGenerator::class)
            ->setArguments([
                new Reference('marshaller.template.value_generators.memory'),
            ])
            ->addTag('marshaller.template.value_generator.memory');

        $container->register('marshaller.template.object_value_generator.memory', MemoryObjectValueGenerator::class)
            ->setArguments([
                new Reference('marshaller.template.value_generators.memory'),
            ])
            ->addTag('marshaller.template.value_generator.memory');

        $container->register('marshaller.template.value_generators.memory', ValueGenerators::class)
            ->setArguments([
                new TaggedIteratorArgument('marshaller.template.value_generator.memory'),
            ]);

        $container->register('marshaller.template.generator.memory', Generator::class)
            ->setArguments([
                new Reference('marshaller.template.structure_generator.memory'),
                new Reference('marshaller.template.value_generators.memory'),
            ]);

        $container->setAlias('marshaller.template.generator', 'marshaller.template.generator.json');

        // Template filename builders
        $container->register('marshaller.template.filename_builder', TemplateFilenameBuilder::class);

        // Template loaders
        $container->register('marshaller.template.printer', Standard::class);
        $container->register('marshaller.template.loader', TemplateLoader::class)
            ->setArguments([
                new Reference('marshaller.template.generator'),
                new Reference('marshaller.template.printer'),
                new Reference('marshaller.template.filename_builder'),
                new Reference('marshaller.metadata.class_factory'),
                new Reference('filesystem'),
                new Parameter('kernel.cache_dir'),
            ]);

        // Context
        $container->register('marshaller.cache.declination.groups', GroupsOptionDeclination::class)
            ->addTag('marshaller.cache.declination');

        $container->register('marshaller.context.declination_resolver', ContextDeclinationResolver::class)
            ->setArguments([
                new Reference('marshaller.metadata.class_factory'),
                new TaggedIteratorArgument('marshaller.cache.declination'),
                new Parameter('marshaller.max_declination'),
            ]);

        // Cache
        $container->register('marshaller.cache.warmable_resolver', WarmableResolver::class)
            ->setArguments([
                new Parameter('marshaller.marshallable_paths'),
            ]);

        $container->register('marshaller.cache.template_warmer', TemplateCacheWarmer::class)
            ->setArguments([
                new Reference('marshaller.cache.warmable_resolver'),
                new Reference('marshaller.template.loader'),
                new Reference('marshaller.context.declination_resolver'),
            ])
            ->addTag('kernel.cache_warmer');
    }
}
