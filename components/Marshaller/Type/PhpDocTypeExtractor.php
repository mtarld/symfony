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

    public function extract(\ReflectionProperty|\ReflectionFunctionAbstract $reflection): ?array
    {
        if ($reflection instanceof \ReflectionProperty) {
            return $this->extractFromProperty($reflection);
        }

        return $this->extractFromReturnType($reflection);
    }

    /**
     * @return list<Type>|null
     */
    public function extractFromProperty(\ReflectionProperty $property): ?array
    {
        try {
            $docBlock = $this->docBlockFactory->create($property, $this->contextFactory->createFromReflector($property->getDeclaringClass()));
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

        return $this->extractFromPropertyInfoTypes($types, $property->getDeclaringClass());
    }

    /**
     * @return list<Type>|null
     */
    // TODO test
    public function extractFromReturnType(\ReflectionFunctionAbstract $function): ?array
    {
        $tag = DocBlockFactory::createInstance()->create($function)->getTagsByName('return')[0] ?? null;
        if (!$tag instanceof Return_) {
            return null;
        }

        $reflectionType = $tag->getType();
        if (null === $docType || [] === ($types = $this->docTypeHelper->getTypes($docType))) {
            return null;
        }

        return $this->extractFromPropertyInfoTypes($types, $function instanceof \ReflectionMethod ? $function->getDeclaringClass() : null);
    }

    /**
     * @param list<PropertyInfoType> $propertyInfoTypes
     *
     * @return list<Type>|null
     */
    private function extractFromPropertyInfoTypes(array $propertyInfoTypes, ?\ReflectionClass $declaringClass): array
    {
        $createTypeFromPropertyInfoType = static function (PropertyInfoType $propertyInfoType) use (&$createTypeFromPropertyInfoType, $declaringClass): Type {
            $className = $propertyInfoType->getClassName();
            if (null !== $declaringClass) {
                $declaringClassName = $declaringClass->getName();

                if ('self' === $className || 'static' === $className) {
                    $className = $declaringClassName;
                } elseif ('parent' === $className && false !== $parentClassName = get_parent_class($declaringClassName)) {
                    $className = $parentClassName;
                }
            }

            return new Type(
                $propertyInfoType->getBuiltinType(),
                $propertyInfoType->isNullable(),
                $className,
                $propertyInfoType->isCollection(),
                array_map(fn ($t): Type => $createTypeFromPropertyInfoType($t), $propertyInfoType->getCollectionKeyTypes()),
                array_map(fn ($t): Type => $createTypeFromPropertyInfoType($t), $propertyInfoType->getCollectionValueTypes()),
            );
        };

        return array_map(fn (PropertyInfoType $t): Type => $createTypeFromPropertyInfoType($t), $propertyInfoTypes);
    }
}
