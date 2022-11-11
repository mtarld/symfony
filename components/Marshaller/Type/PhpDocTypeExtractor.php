<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\ContextFactory;
use Symfony\Component\PropertyInfo\Type as PropertyInfoType;
use Symfony\Component\PropertyInfo\Util\PhpDocTypeHelper;

final class PhpDocTypeExtractor
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

    public function extract(\ReflectionProperty|\ReflectionFunctionAbstract $reflection, \ReflectionClass $declaringClass): ?Type
    {
        if ($reflection instanceof \ReflectionProperty) {
            return $this->extractFromProperty($reflection, $declaringClass);
        }

        return $this->extractFromReturnType($reflection, $declaringClass);
    }

    public function extractFromProperty(\ReflectionProperty $property, \ReflectionClass $declaringClass): ?Type
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

        if (null === $reflectionType = $tag->getType()) {
            return null;
        }

        if (1 !== \count($types = $this->docTypeHelper->getTypes($reflectionType))) {
            return null;
        }

        return $this->createFromPropertyInfoType($types[0], $declaringClass);
    }

    // TODO test
    public function extractFromReturnType(\ReflectionFunctionAbstract $function, \ReflectionClass $declaringClass): ?Type
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

        if (null === $reflectionType = $tag->getType()) {
            return null;
        }

        if (1 !== \count($types = $this->docTypeHelper->getTypes($reflectionType))) {
            return null;
        }

        return $this->createFromPropertyInfoType($types[0], $declaringClass);
    }

    private function createFromPropertyInfoType(PropertyInfoType $propertyInfoType, \ReflectionClass $declaringClass): Type
    {
        if (\count($collectionKeyTypes = $propertyInfoType->getCollectionKeyTypes()) > 1) {
            return null;
        }

        if (\count($collectionValueTypes = $propertyInfoType->getCollectionValueTypes()) > 1) {
            return null;
        }

        $className = $propertyInfoType->getClassName();
        $declaringClassName = $declaringClass->getName();

        if ('self' === $className || 'static' === $className) {
            $className = $declaringClassName;
        } elseif ('parent' === $className && false !== $parentClassName = get_parent_class($declaringClassName)) {
            $className = $parentClassName;
        }

        $collectionKeyType = null;
        if (isset($collectionKeyTypes[0])) {
            $collectionKeyType = $this->createFromPropertyInfoType($collectionKeyTypes[0], $declaringClass);
        }

        $collectionValueType = null;
        if (isset($collectionValueTypes[0])) {
            $collectionValueType = $this->createFromPropertyInfoType($collectionValueTypes[0], $declaringClass);
        }

        return new Type(
            $propertyInfoType->getBuiltinType(),
            $propertyInfoType->isNullable(),
            $className,
            $propertyInfoType->isCollection(),
            $collectionKeyType,
            $collectionValueType,
        );
    }
}
