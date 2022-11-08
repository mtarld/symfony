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

final class PhpstanTypeExtractor
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

    public function extract(\ReflectionProperty|\ReflectionFunctionAbstract $reflection, \ReflectionClass $declaringClass): ?Type
    {
        if ($reflection instanceof \ReflectionProperty) {
            return $this->extractFromProperty($reflection, $declaringClass);
        }

        return $this->extractFromReturnType($reflection, $declaringClass);
    }

    public function extractFromProperty(\ReflectionProperty $property, \ReflectionClass $declaringClass): ?Type
    {
        if (null === $docNode = $this->getDocNode($property)) {
            return null;
        }

        $tag = $docNode->getTagsByName('@var')[0] ?? null;
        if (null === $tag || $tag->value instanceof InvalidTagValueNode) {
            return null;
        }

        $nameScope = $this->nameScopeFactory->create($declaringClass->getName());

        if (\count($types = $this->docTypeHelper->getTypes($tag->value, $nameScope)) > 1) {
            return null;
        }

        return $this->createFromPropertyInfoType($types[0], $declaringClass);
    }

    public function extractFromReturnType(\ReflectionFunctionAbstract $function, \ReflectionClass $declaringClass): ?Type
    {
        if (null === $docNode = $this->getDocNode($function)) {
            return null;
        }

        $tag = $docNode->getTagsByName('@return')[0] ?? null;
        if (null === $tag || $tag->value instanceof InvalidTagValueNode) {
            return null;
        }

        $nameScope = $this->nameScopeFactory->create($declaringClass->getName());

        if (\count($types = $this->docTypeHelper->getTypes($tag->value, $nameScope)) > 1) {
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

        return new Type(
            $propertyInfoType->getBuiltinType(),
            $propertyInfoType->isNullable(),
            $className,
            $propertyInfoType->isCollection(),
            $collectionKeyTypes[0],
            $collectionValueTypes[0],
        );
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
