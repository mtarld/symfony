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
use Symfony\Component\Serializer\Serialize\Configuration\Configuration;
use Symfony\Component\Serializer\Serialize\Mapping\PropertyMetadata;
use Symfony\Component\Serializer\Serialize\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\Serializer\Serialize\VariableNameScoperTrait;
use Symfony\Component\Serializer\Type\Type;
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
        private readonly PropertyMetadataLoaderInterface $propertyMetadataLoader,
    ) {
        $this->typeSorter = new TypeSorter();
    }

    public function build(Type $type, string $accessor, Configuration $configuration, array $context): DomNode
    {
        if ($type->isNullable()) {
            return new UnionDomNode($accessor, [
                new ValueDomNode($accessor, 'null'),
                $this->build(Type::createFromString(substr((string) $type, 1)), $accessor, $configuration, $context),
            ]);
        }

        if ($type->isUnion()) {
            return new UnionDomNode($accessor, array_map(
                fn (Type $t): DomNode => $this->build($t, $accessor, $configuration, $context),
                $this->typeSorter->sortByPrecision($type->unionTypes()),
            ));
        }

        if ($type->isObject() && $type->hasClass()) {
            $className = $type->className();

            if (isset($context['generated_classes'][$className])) {
                throw new CircularReferenceException($className);
            }

            $context['generated_classes'][$className] = true;
            $context['accessor'] = $accessor;

            $propertiesMetadata = $this->propertyMetadataLoader->load($className, $configuration, $context);

            $propertiesDomNodes = array_map(
                fn (PropertyMetadata $p) => $this->build($p->type, $p->accessor, $configuration, $context),
                $propertiesMetadata,
            );

            return new ObjectDomNode($accessor, $type->className(), $propertiesDomNodes);
        }

        if ($type->isEnum()) {
            $backedType = (new \ReflectionEnum($type->className()))->getBackingType();
            if (null === $backedType) {
                throw new \RuntimeException('TODO');
            }

            return new ValueDomNode(sprintf('%s->value', $accessor), Type::createFromString((string) $backedType));
        }

        if ($type->isCollection()) {
            return new CollectionDomNode(
                $accessor,
                $this->build($type->collectionValueType(), '$'.$this->scopeVariableName('value', $context), $configuration, $context),
                $type->isList(),
                'array' === $type->name(),
            );
        }

        return new ValueDomNode($accessor, (string) $type);
    }
}
