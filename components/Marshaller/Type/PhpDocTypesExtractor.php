<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\ContextFactory;
use Symfony\Component\PropertyInfo\Type as PropertyInfoType;
use Symfony\Component\PropertyInfo\Util\PhpDocTypeHelper;

final class PhpDocTypesExtractor
{
    private readonly DocBlockFactory $docBlockFactory;
    private readonly ContextFactory $contextFactory;
    private readonly PhpDocTypeHelper $docTypeHelper; // TODO this dependency might not be wanted

    public function __construct()
    {
        $this->docBlockFactory = DocBlockFactory::createInstance();
        $this->contextFactory = new ContextFactory();
        $this->docTypeHelper = new PhpDocTypeHelper();
    }

    public function extract(\ReflectionProperty|\ReflectionFunctionAbstract $reflection, \ReflectionClass $declaringClass): ?Types
    {
        if ($reflection instanceof \ReflectionProperty) {
            return $this->extractFromProperty($reflection, $declaringClass);
        }

        return $this->extractFromReturnType($reflection, $declaringClass);
    }

    public function extractFromProperty(\ReflectionProperty $property, \ReflectionClass $declaringClass): ?Types
    {
        try {
            $docBlock = $this->docBlockFactory->create($property, $this->contextFactory->createFromReflector($declaringClass));
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return null;
        }

        $tag = $docBlock->getTagsByName('var')[0] ?? null;
        if (!$tag instanceof Var_) {
            return null;
        }

        $reflectionType = $tag->getType();
        if (null === $reflectionType || [] === ($types = $this->docTypeHelper->getTypes($reflectionType))) {
            return null;
        }

        return $this->createFromPropertyInfoTypes($types, $declaringClass);
    }

    // TODO test
    public function extractFromReturnType(\ReflectionFunctionAbstract $function, \ReflectionClass $declaringClass): ?Types
    {
        try {
            $docBlock = $this->docBlockFactory->create($function, $this->contextFactory->createFromReflector($declaringClass));
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return null;
        }

        $tag = $docBlock->getTagsByName('return')[0] ?? null;
        if (!$tag instanceof Return_) {
            return null;
        }

        $reflectionType = $tag->getType();
        if (null === $reflectionType || [] === ($types = $this->docTypeHelper->getTypes($reflectionType))) {
            return null;
        }

        return $this->createFromPropertyInfoTypes($types, $declaringClass);
    }

    /**
     * @param list<PropertyInfoType> $propertyInfoTypes
     */
    private function createFromPropertyInfoTypes(array $propertyInfoTypes, \ReflectionClass $declaringClass): Types
    {
        $createTypeFromPropertyInfoType = static function (PropertyInfoType $propertyInfoType) use (&$createTypeFromPropertyInfoType, $declaringClass): Type {
            $className = $propertyInfoType->getClassName();
            $declaringClassName = $declaringClass->getName();

            if ('self' === $className || 'static' === $className) {
                $className = $declaringClassName;
            } elseif ('parent' === $className && false !== $parentClassName = get_parent_class($declaringClassName)) {
                $className = $parentClassName;
            }

            $collectionKeyTypes = $propertyInfoType->getCollectionKeyTypes()
                ? new Types(array_map(fn ($t): Type => $createTypeFromPropertyInfoType($t), $propertyInfoType->getCollectionKeyTypes()))
                : null;

            $collectionValueTypes = $propertyInfoType->getCollectionValueTypes()
                ? new Types(array_map(fn ($t): Type => $createTypeFromPropertyInfoType($t), $propertyInfoType->getCollectionValueTypes()))
                : null;

            return new Type(
                $propertyInfoType->getBuiltinType(),
                $propertyInfoType->isNullable(),
                $className,
                $propertyInfoType->isCollection(),
                $collectionKeyTypes,
                $collectionValueTypes,
            );
        };

        return new Types(array_map(fn (PropertyInfoType $t): Type => $createTypeFromPropertyInfoType($t), $propertyInfoTypes));
    }
}
