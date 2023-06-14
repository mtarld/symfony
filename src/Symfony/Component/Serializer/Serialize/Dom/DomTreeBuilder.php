<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Dom;

use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Serialize\PropertyConfigurator\PropertyConfiguratorInterface;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\VariableNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\VariableNameScoperTrait;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\NodeInterface;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\PropertyNode;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;
use Symfony\Component\Serializer\Type\TypeFactory;
use Symfony\Component\Serializer\Type\TypeSorter;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class DomTreeBuilder implements DomTreeBuilderInterface
{
    use VariableNameScoperTrait;

    private readonly TypeSorter $typeSorter;

    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
        private readonly PropertyConfiguratorInterface $propertyConfigurator,
    ) {
        $this->typeSorter = new TypeSorter();
    }

    public function build(Type $type, NodeInterface $accessor, array $context): DomNode
    {
        if ($type->isNullable()) {
            return new UnionDomNode($accessor, [
                new ValueDomNode($accessor, 'null'),
                $this->build(TypeFactory::createFromString(substr((string) $type, 1)), $accessor, $context),
            ]);
        }

        if ($type->isUnion()) {
            return new UnionDomNode($accessor, array_map(
                fn (Type $t): DomNode => $this->build($t, $accessor, $context),
                $this->typeSorter->sortByPrecision($type->unionTypes()),
            ));
        }

        if ($type->isObject() && $type->hasClass()) {
            if (isset($context['generated_classes'][$type->className()])) {
                throw new CircularReferenceException($type->className());
            }

            $context['generated_classes'][$type->className()] = true;

            $properties = [];
            foreach ((new \ReflectionClass($type->className()))->getProperties() as $property) {
                if (!$property->isPublic()) {
                    continue;
                }

                $properties[$property->getName()] = $this->build(
                    $this->typeExtractor->extractFromProperty($property),
                    new PropertyNode($accessor, $property->getName()),
                    $context,
                );
            }

            $properties = $this->propertyConfigurator->configure($type->className(), $properties, $context);

            return new ObjectDomNode($accessor, $type->className(), $properties);
        }

        if ($type->isEnum()) {
            $backedType = (new \ReflectionEnum($type->className()))->getBackingType();
            if (null === $backedType) {
                throw new \RuntimeException('TODO');
            }

            return new ValueDomNode(new PropertyNode($accessor, 'value'), TypeFactory::createFromString((string) $backedType));
        }

        if ($type->isCollection()) {
            return new CollectionDomNode(
                $accessor,
                $this->build($type->collectionValueType(), new VariableNode($this->scopeVariableName('value', $context)), $context),
                $type->isList(),
                'array' === $type->name(),
            );
        }

        return new ValueDomNode($accessor, (string) $type);
    }
}
