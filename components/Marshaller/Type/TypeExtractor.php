<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

use Symfony\Component\PropertyInfo\Type as PropertyInfoType;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Type as DocType;
use Symfony\Component\PropertyInfo\Util\PhpDocTypeHelper;

// TODO use PHPStan extractor instead
final class TypeExtractor
{
    // TODO this dependency might not be wanted
    private readonly PhpDocTypeHelper $docTypeHelper;

    public function __construct()
    {
        $this->docTypeHelper = new PhpDocTypeHelper();
    }

    /**
     * @return list<Type>
     */
    public function extract(\ReflectionProperty|\ReflectionFunction $reflection): array
    {
        $docType = $reflection instanceof \ReflectionProperty
            ? $this->extractDocTypeFromProperty($reflection)
            : $this->extractDocTypeFromFunction($reflection)
        ;

        if (null === $docType) {
            return [];
        }

        $fromPropertyInfoType = static function (PropertyInfoType $propertyInfoType) use (&$fromPropertyInfoType): Type {
            return new Type(
                $propertyInfoType->getBuiltinType(),
                $propertyInfoType->isNullable(),
                $propertyInfoType->getClassName(),
                array_map(fn ($t): Type => $fromPropertyInfoType($t), $propertyInfoType->getCollectionKeyTypes()),
                array_map(fn ($t): Type => $fromPropertyInfoType($t), $propertyInfoType->getCollectionValueTypes()),
            );
        };

        return array_map(fn (PropertyInfoType $t): Type => $fromPropertyInfoType($t), $this->docTypeHelper->getTypes($docType));
    }

    private function extractDocTypeFromProperty(\ReflectionProperty $property): ?DocType
    {
        $tag = DocBlockFactory::createInstance()->create($property)->getTagsByName('var')[0] ?? null;
        if (null == $tag) {
            return null;
        }

        if (!$tag instanceof Var_) {
            return null;
        }

        return $tag->getType();
    }

    private function extractDocTypeFromFunction(\ReflectionFunction $function): ?DocType
    {
        $tag = DocBlockFactory::createInstance()->create($function)->getTagsByName('return')[0] ?? null;
        if (null == $tag) {
            return null;
        }

        if (!$tag instanceof Return_) {
            return null;
        }

        return $tag->getType();
    }
}
