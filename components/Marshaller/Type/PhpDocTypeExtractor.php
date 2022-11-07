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

    public function extract(\ReflectionProperty|\ReflectionFunction $reflection): ?array
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
    public function extractFromReturnType(\ReflectionFunction $function): ?array
    {
        $tag = DocBlockFactory::createInstance()->create($function)->getTagsByName('return')[0] ?? null;
        if (!$tag instanceof Return_) {
            return null;
        }

        $reflectionType = $tag->getType();
        if (null === $docType || [] === ($types = $this->docTypeHelper->getTypes($docType))) {
            return null;
        }

        return $this->extractFromPropertyInfoTypes($types);
    }

    /**
     * @param list<PropertyInfoType> $propertyInfoTypes
     *
     * @return list<Type>|null
     */
    private function extractFromPropertyInfoTypes(array $propertyInfoTypes): ?array
    {
        $createTypeFromPropertyInfoType = static function (PropertyInfoType $propertyInfoType) use (&$createTypeFromPropertyInfoType): Type {
            return new Type(
                $propertyInfoType->getBuiltinType(),
                $propertyInfoType->isNullable(),
                $propertyInfoType->getClassName(),
                $propertyInfoType->isCollection(),
                array_map(fn ($t): Type => $createTypeFromPropertyInfoType($t), $propertyInfoType->getCollectionKeyTypes()),
                array_map(fn ($t): Type => $createTypeFromPropertyInfoType($t), $propertyInfoType->getCollectionValueTypes()),
            );
        };

        $types = array_map(fn (PropertyInfoType $t): Type => $createTypeFromPropertyInfoType($t), $propertyInfoTypes);
        dd($types);

        $parentClass = null;
        $class = $property->getDeclaringClass()->getName();

        foreach ($docBlockTypes as $type) {
            switch ($type->className()) {
                case 'self':
                case 'static':
                    $resolvedClass = $class;
                    break;

                case 'parent':
                    if (false !== $resolvedClass = $parentClass ?? $parentClass = get_parent_class($class)) {
                        break;
                    }
                    // no break

                default:
                    $types[] = $type;
            }

            $types[] = new Type('object', $type->isNullable(), $resolvedClass, $type->isCollection(), $type->collectionKeyTypes(), $type->collectionValueTypes());
        }

        if (!isset($types[0])) {
            return null;
        }

        return [new Type(name: 'array', collection: true, collectionKeyTypes: new Type('int'), collectionValueTypes: $types[0])];
    }
}
