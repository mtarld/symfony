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

    public function extractFromProperty(\ReflectionProperty $property): ?string
    {
        if (null === $docNode = $this->getDocNode($property)) {
            return null;
        }

        $tag = $docNode->getTagsByName('@var')[0] ?? null;
        if (null === $tag || $tag->value instanceof InvalidTagValueNode) {
            return null;
        }

        $nameScope = $this->nameScopeFactory->create($property->getDeclaringClass()->getName());

        return $this->createFromPropertyInfoTypes($this->docTypeHelper->getTypes($tag->value, $nameScope), $property->getDeclaringClass());
    }

    public function extractFromReturnType(\ReflectionFunction $function): ?string
    {
        if (null === $docNode = $this->getDocNode($function)) {
            return null;
        }

        $tag = $docNode->getTagsByName('@return')[0] ?? null;
        if (null === $tag || $tag->value instanceof InvalidTagValueNode) {
            return null;
        }

        $nameScope = $this->nameScopeFactory->create($function->getClosureScopeClass()->getName());

        return $this->createFromPropertyInfoTypes($this->docTypeHelper->getTypes($tag->value, $nameScope), $function->getClosureScopeClass());
    }

    /**
     * @param list<PropertyInfoType> $propertyInfoTypes
     */
    private function createFromPropertyInfoTypes(array $propertyInfoTypes, \ReflectionClass $declaringClass): string
    {
        return implode('|', array_map(fn (PropertyInfoType $t): string => $this->createFromPropertyInfoType($t, $declaringClass), $propertyInfoTypes));
    }

    private function createFromPropertyInfoType(PropertyInfoType $propertyInfoType, \ReflectionClass $declaringClass): string
    {
        $nullablePrefix = $propertyInfoType->isNullable() ? '?' : '';

        if (null !== $propertyInfoType->getClassName()) {
            $className = $propertyInfoType->getClassName();
            $declaringClassName = $declaringClass->getName();

            if ('self' === $className || 'static' === $className) {
                $className = $declaringClassName;
            } elseif ('parent' === $className && false !== $parentClassName = get_parent_class($declaringClassName)) {
                $className = $parentClassName;
            }

            return $nullablePrefix.$className;
        }

        if ($propertyInfoType->isCollection()) {
            $collectionKeyTypes = $propertyInfoType->getCollectionKeyTypes();
            $collectionValueTypes = $propertyInfoType->getCollectionValueTypes();

            $collectionKeyType = 'int';
            if (isset($collectionKeyTypes[0])) {
                $collectionKeyType = $this->createFromPropertyInfoTypes($collectionKeyTypes, $declaringClass);
            }

            $collectionValueType = $this->createFromPropertyInfoTypes($collectionValueTypes, $declaringClass);

            return $nullablePrefix.sprintf('array<%s, %s>', $collectionKeyType, $collectionValueType);
        }

        return $nullablePrefix.$propertyInfoType->getBuiltinType();
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
