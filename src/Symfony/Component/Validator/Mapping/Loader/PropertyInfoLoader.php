<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Mapping\Loader;

use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\TypeInfo\BackwardCompatibilityHelper;
use Symfony\Component\TypeInfo\Type as TypeInfoType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\IntersectionType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Mapping\AutoMappingStrategy;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Guesses and loads the appropriate constraints using PropertyInfo.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class PropertyInfoLoader implements LoaderInterface
{
    use AutoMappingTrait;

    private PropertyListExtractorInterface $listExtractor;
    private PropertyTypeExtractorInterface $typeExtractor;
    private PropertyAccessExtractorInterface $accessExtractor;
    private ?string $classValidatorRegexp;

    public function __construct(PropertyListExtractorInterface $listExtractor, PropertyTypeExtractorInterface $typeExtractor, PropertyAccessExtractorInterface $accessExtractor, ?string $classValidatorRegexp = null)
    {
        $this->listExtractor = $listExtractor;
        $this->typeExtractor = $typeExtractor;
        $this->accessExtractor = $accessExtractor;
        $this->classValidatorRegexp = $classValidatorRegexp;
    }

    public function loadClassMetadata(ClassMetadata $metadata): bool
    {
        $className = $metadata->getClassName();
        if (!$properties = $this->listExtractor->getProperties($className)) {
            return false;
        }

        $loaded = false;
        $enabledForClass = $this->isAutoMappingEnabledForClass($metadata, $this->classValidatorRegexp);
        foreach ($properties as $property) {
            if (false === $this->accessExtractor->isWritable($className, $property)) {
                continue;
            }

            if (!property_exists($className, $property)) {
                continue;
            }

            $enabledForProperty = $enabledForClass;
            $hasTypeConstraint = false;
            $hasNotNullConstraint = false;
            $hasNotBlankConstraint = false;
            $allConstraint = null;
            foreach ($metadata->getPropertyMetadata($property) as $propertyMetadata) {
                // Enabling or disabling auto-mapping explicitly always takes precedence
                if (AutoMappingStrategy::DISABLED === $propertyMetadata->getAutoMappingStrategy()) {
                    continue 2;
                }

                if (AutoMappingStrategy::ENABLED === $propertyMetadata->getAutoMappingStrategy()) {
                    $enabledForProperty = true;
                }

                foreach ($propertyMetadata->getConstraints() as $constraint) {
                    if ($constraint instanceof Type) {
                        $hasTypeConstraint = true;
                    } elseif ($constraint instanceof NotNull) {
                        $hasNotNullConstraint = true;
                    } elseif ($constraint instanceof NotBlank) {
                        $hasNotBlankConstraint = true;
                    } elseif ($constraint instanceof All) {
                        $allConstraint = $constraint;
                    }
                }
            }

            if (!$enabledForProperty) {
                continue;
            }

            $loaded = true;

            if ($hasTypeConstraint) {
                continue;
            }

            if (null === $type = $this->getPropertyType($className, $property)) {
                continue;
            }

            $nullable = false;

            if ($type instanceof UnionType && $type->isNullable()) {
                $nullable = true;
                $type = $type->asNonNullable();
            }

            if ($type instanceof CollectionType) {
                $this->handleAllConstraint($property, $allConstraint, $type->getCollectionValueType(), $metadata);
            }

            if (null !== $typeConstraint = $this->getTypeConstraint($type)) {
                $metadata->addPropertyConstraint($property, $typeConstraint);
            }

            if (!$nullable && !$hasNotBlankConstraint && !$hasNotNullConstraint) {
                $metadata->addPropertyConstraint($property, new NotNull());
            }
        }

        return $loaded;
    }

    /**
     * BC Layer for Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface::getTypes.
     */
    private function getPropertyType(string $className, string $property): ?TypeInfoType
    {
        if (method_exists($this->typeExtractor, 'getType')) {
            return $this->typeExtractor->getType($className, $property);
        }

        return BackwardCompatibilityHelper::convertLegacyTypesToType($this->typeExtractor->getTypes($className, $property));
    }

    private function getTypeConstraint(TypeInfoType $type): ?Type
    {
        if ($type instanceof UnionType || $type instanceof IntersectionType) {
            return ($type->isA(TypeIdentifier::INT) || $type->isA(TypeIdentifier::FLOAT) || $type->isA(TypeIdentifier::STRING) || $type->isA(TypeIdentifier::BOOL)) ? new Type(['type' => 'scalar']) : null;
        }

        $baseType = $type->getBaseType();

        if ($baseType instanceof ObjectType) {
            return new Type(['type' => $baseType->getClassName()]);
        }

        if (TypeIdentifier::MIXED !== $baseType->getTypeIdentifier()) {
            return new Type(['type' => $baseType->getTypeIdentifier()->value]);
        }

        return null;
    }

    private function handleAllConstraint(string $property, ?All $allConstraint, TypeInfoType $typeInfoType, ClassMetadata $metadata): void
    {
        $containsTypeConstraint = false;
        $containsNotNullConstraint = false;
        if (null !== $allConstraint) {
            foreach ($allConstraint->constraints as $constraint) {
                if ($constraint instanceof Type) {
                    $containsTypeConstraint = true;
                } elseif ($constraint instanceof NotNull) {
                    $containsNotNullConstraint = true;
                }
            }
        }

        $constraints = [];
        if (!$containsNotNullConstraint && !$typeInfoType->isNullable()) {
            $constraints[] = new NotNull();
        }

        if (!$containsTypeConstraint && null !== $typeConstraint = $this->getTypeConstraint($typeInfoType)) {
            $constraints[] = $typeConstraint;
        }

        if ([] === $constraints) {
            return;
        }

        if (null === $allConstraint) {
            $metadata->addPropertyConstraint($property, new All(['constraints' => $constraints]));
        } else {
            $allConstraint->constraints = array_merge($allConstraint->constraints, $constraints);
        }
    }
}
