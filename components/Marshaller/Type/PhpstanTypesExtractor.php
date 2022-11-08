<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Symfony\Component\PropertyInfo\PhpStan\NameScopeFactory;
use Symfony\Component\PropertyInfo\Type as PropertyInfoType;
use Symfony\Component\PropertyInfo\Util\PhpStanTypeHelper;

final class PhpstanTypesExtractor
{
    private readonly PhpStanTypeHelper $docTypeHelper;
    private readonly PhpDocParser $docParser;
    private readonly Lexer $lexer;
    private readonly NameScopeFactory $nameScopeFactory;

    public function __construct(
    ) {
        $this->docTypeHelper = new PhpStanTypeHelper();
        $this->docParser = new PhpDocParser(new TypeParser(new ConstExprParser()), new ConstExprParser());
        $this->lexer = new Lexer();
        $this->nameScopeFactory = new NameScopeFactory();
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
        if (null === $docNode = $this->getDocNode($property)) {
            return null;
        }

        $tag = $docNode->getTagsByName('@var')[0] ?? null;
        if (null === $tag || $tag->value instanceof InvalidTagValueNode) {
            return null;
        }

        $nameScope = $this->nameScopeFactory->create($declaringClass->getName());

        return $this->createFromPropertyInfoTypes($this->docTypeHelper->getTypes($tag->value, $nameScope), $declaringClass);
    }

    public function extractFromReturnType(\ReflectionFunctionAbstract $function, \ReflectionClass $declaringClass): ?Types
    {
        if (null === $docNode = $this->getDocNode($function)) {
            return null;
        }

        $tag = $docNode->getTagsByName('@return')[0] ?? null;
        if (null === $tag || $tag->value instanceof InvalidTagValueNode) {
            return null;
        }

        $nameScope = $this->nameScopeFactory->create($declaringClass->getName());

        return $this->createFromPropertyInfoTypes($this->docTypeHelper->getTypes($tag->value, $nameScope), $declaringClass);
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

    private function getDocNode(\ReflectionProperty|\ReflectionFunctionAbstract $reflection): ?PhpDocNode
    {
        if (null === $rawDocNode = $reflection->getDocComment() ?: null) {
            return null;
        }

        $tokens = new TokenIterator($this->lexer->tokenize($rawDocNode));
        $docNode = $this->docParser->parse($tokens);
        $tokens->consumeTokenType(Lexer::TOKEN_END);

        return $docNode;
    }
}
