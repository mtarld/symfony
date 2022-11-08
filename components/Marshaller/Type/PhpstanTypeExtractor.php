<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Symfony\Component\PropertyInfo\Type as PropertyInfoType;
use Symfony\Component\PropertyInfo\Util\PhpDocTypeHelper;

final class PhpstanTypeExtractor
{
    // TODO this dependencies might not be wanted
    private readonly PhpDocTypeHelper $docTypeHelper;
    private readonly PhpDocParser $phpDocParser;
    private readonly Lexer $lexer;

    public function __construct(
    ) {
        $this->docTypeHelper = new PhpDocTypeHelper();
        $this->phpDocParser = new PhpDocParser(new TypeParser(new ConstExprParser()), new ConstExprParser());
        $this->lexer = new Lexer();
    }

    /**
     * @return list<Type>
     */
    public function extract(\ReflectionProperty|\ReflectionFunctionAbstract $reflection): ?array
    {
        return null;

        if ($reflection instanceof \ReflectionProperty) {
            return $this->extractFromProperty($reflection);
        }

        return $this->extractFromReturnType($reflection);
    }

    /**
     * @return list<Type>
     */
    public function extractFromProperty(\ReflectionProperty $property): ?array
    {
        if (null === $rawDocNode = $property->getDocComment() ?: null) {
            // TODO fallback
            return [];
        }

        $tokens = new TokenIterator($this->lexer->tokenize($rawDocNode));
        $docNode = $this->phpDocParser->parse($tokens);

        $tokens->consumeTokenType(Lexer::TOKEN_END);

        $tag = $docNode->create($property)->getTagsByName('var')[0] ?? null;
        dd($tag);
        if (!$tag instanceof Var_) {
            throw new \RuntimeException(sprintf('Cannot retrieve "@var" tag from docblock of "%s::$%s"', $reflection->getDeclaringClass()->getName(), $reflection->getName()));
        }

        $reflectionType = $tag->getType();
        if (null === $docType || [] === ($types = $this->docTypeHelper->getTypes($docType))) {
            throw new \RuntimeException(sprintf('Cannot retrieve type from docblock of "%s::$%s"', $reflection->getDeclaringClass()->getName(), $reflection->getName()));
        }

        return $this->extractFromPropertyInfoTypes($types);
    }

    /**
     * @return list<Type>
     */
    public function extractFromReturnType(\ReflectionFunctionAbstract $function): ?array
    {
        $tag = DocBlockFactory::createInstance()->create($function)->getTagsByName('return')[0] ?? null;
        if (!$tag instanceof Return_) {
            throw new \RuntimeException(sprintf('Cannot retrieve "@return" tag from docblock of "%s::%s()"', $reflection->getDeclaringClass()->getName(), $reflection->getName()));
        }

        $reflectionType = $tag->getType();
        if (null === $docType || [] === ($types = $this->docTypeHelper->getTypes($docType))) {
            throw new \RuntimeException(sprintf('Cannot retrieve type from docblock of "%s::%s()"', $reflection->getDeclaringClass()->getName(), $reflection->getName()));
        }

        return $this->extractFromPropertyInfoTypes($types);
    }

    /**
     * @param list<PropertyInfoType> $propertyInfoTypes
     *
     * @return list<Type>
     */
    private function extractFromPropertyInfoTypes(array $propertyInfoTypes): array
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

    private function getDocBlockFromProperty(\ReflectionProperty $property): ?array
    {
        if (null === $rawDocNode = $property->getDocComment() ?: null) {
            return null;
        }

        $tokens = new TokenIterator($this->lexer->tokenize($rawDocNode));
        $docNode = $this->phpDocParser->parse($tokens);
        $tokens->consumeTokenType(Lexer::TOKEN_END);

        return $docNode;
    }

    /**
     * @return array{PhpDocNode, string, string}|null
     */
    private function getDocBlockFromMethod(string $class, string $ucFirstProperty, int $type): ?array
    {
        $prefixes = self::ACCESSOR === $type ? $this->accessorPrefixes : $this->mutatorPrefixes;
        $prefix = null;

        foreach ($prefixes as $prefix) {
            $methodName = $prefix.$ucFirstProperty;

            try {
                $reflectionMethod = new \ReflectionMethod($class, $methodName);
                if ($reflectionMethod->isStatic()) {
                    continue;
                }

                if (
                    (self::ACCESSOR === $type && 0 === $reflectionMethod->getNumberOfRequiredParameters())
                    || (self::MUTATOR === $type && $reflectionMethod->getNumberOfParameters() >= 1)
                ) {
                    break;
                }
            } catch (\ReflectionException $e) {
                // Try the next prefix if the method doesn't exist
            }
        }

        if (!isset($reflectionMethod)) {
            return null;
        }

        if (null === $rawDocNode = $reflectionMethod->getDocComment() ?: null) {
            return null;
        }

        $tokens = new TokenIterator($this->lexer->tokenize($rawDocNode));
        $phpDocNode = $this->phpDocParser->parse($tokens);
        $tokens->consumeTokenType(Lexer::TOKEN_END);

        return [$phpDocNode, $prefix, $reflectionMethod->class];
    }
}
