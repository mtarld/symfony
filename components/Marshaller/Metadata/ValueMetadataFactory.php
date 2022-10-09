<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Metadata;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\DepthOption;
use Symfony\Component\Marshaller\Metadata\Attribute\Attributes;
use Symfony\Component\Marshaller\Metadata\Attribute\FormatterAttribute;
use Symfony\Component\Marshaller\Metadata\Type\MethodReturnTypeExtractor;
use Symfony\Component\Marshaller\Metadata\Type\PropertyTypeExtractor;
use Symfony\Component\Marshaller\Metadata\Type\Type;

final class ValueMetadataFactory
{
    public function __construct(
        private readonly ServiceLocator $locator,
        private readonly PropertyTypeExtractor $propertyTypeExtractor,
        private readonly MethodReturnTypeExtractor $methodReturnTypeExtractor,
        private readonly int $defaultMaxDepth,
        private readonly bool $defaultRejectCircularReference,
    ) {
    }

    public function forProperty(\ReflectionProperty $property, Attributes $attributes, Context $context, array $factoryContext = []): ValueMetadata
    {
        if ($attributes->has(FormatterAttribute::class)) {
            return $this->forPropertyWithFormatter($attributes->get(FormatterAttribute::class), $context, $factoryContext);
        }

        return $this->createFromType($this->propertyTypeExtractor->extract($property->getDeclaringClass()->getName(), $property->getName()), $context, $factoryContext);
    }

    private function forPropertyWithFormatter(FormatterAttribute $formatterAttribute, Context $context, array $factoryContext): ValueMetadata
    {
        $type = $this->methodReturnTypeExtractor->extract($formatterAttribute->class, $formatterAttribute->method);

        return $this->createFromType($type, $context, $factoryContext);
    }

    private function createFromType(Type $type, Context $context, array $factoryContext): ValueMetadata
    {
        $class = null;

        if (null !== ($className = $type->className)) {
            /** @var DepthOption|null $depthOption */
            $depthOption = $context->has(DepthOption::class) ? $context->get(DepthOption::class) : null;

            $maxDepth = $depthOption?->depth ?? $this->defaultMaxDepth;
            $rejectCircularReference = $depthOption?->rejectCircularReference ?? $this->defaultRejectCircularReference;

            $factoryContext['depth'] = isset($factoryContext['depth']) ? $factoryContext['depth'] + 1 : 0;
            if ($factoryContext['depth'] >= $maxDepth) {
                return ValueMetadata::createNone();
            }

            if (isset($factoryContext['classes'][$className]) && $rejectCircularReference) {
                throw new \RuntimeException('circular');
            }

            $factoryContext['classes'][$className] = true;

            $class = $this->locator->get(ClassMetadataFactory::class)->forClass(new \ReflectionClass($className), $context, $factoryContext);

            unset($factoryContext['classes'], $factoryContext['depth']);
        }

        $collectionKey = array_map(fn (Type $t): ValueMetadata => $this->createFromType($t, $context, $factoryContext), $type->collectionKeyTypes);
        foreach ($collectionKey as $valueMetadata) {
            if ($valueMetadata->isNone()) {
                return ValueMetadata::createNone();
            }
        }

        $collectionValue = array_map(fn (Type $t): ValueMetadata => $this->createFromType($t, $context, $factoryContext), $type->collectionValueTypes);
        foreach ($collectionValue as $valueMetadata) {
            if ($valueMetadata->isNone()) {
                return ValueMetadata::createNone();
            }
        }

        return new ValueMetadata($type->builtinType, $type->nullable, $collectionKey, $collectionValue, $class);
    }
}
