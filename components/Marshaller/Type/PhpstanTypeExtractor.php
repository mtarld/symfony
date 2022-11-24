<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;

final class PhpstanTypeExtractor implements TypeExtractorInterface
{
    private readonly PhpstanTypeHelper $phpstanTypeHelper;
    private readonly PhpDocParser $phpstanDocParser;
    private readonly Lexer $phpstanLexer;

    public function __construct(
        private readonly TypeExtractorInterface $decoratedTypeExtractor,
    ) {
        $this->phpstanTypeHelper = new PhpstanTypeHelper();
        $this->phpstanDocParser = new PhpDocParser(new TypeParser(new ConstExprParser()), new ConstExprParser());
        $this->phpstanLexer = new Lexer();
    }

    public function extractFromProperty(\ReflectionProperty $property): string
    {
        if (null === $type = $this->getTypeNode($property)) {
            return $this->decoratedTypeExtractor->extractFromProperty($property);
        }

        return $this->phpstanTypeHelper->getType($type, $property->getDeclaringClass()->getName());
    }

    public function extractFromReturnType(\ReflectionFunctionAbstract $function): string
    {
        if (null === $type = $this->getTypeNode($function)) {
            return $this->decoratedTypeExtractor->extractFromReturnType($function);
        }

        $declaringClass = $function instanceof \ReflectionMethod ? $function->getDeclaringClass() : $function->getClosureScopeClass();

        return $this->phpstanTypeHelper->getType($type, $declaringClass->getName());
    }

    private function getTypeNode(\ReflectionProperty|\ReflectionFunctionAbstract $reflection): ?TypeNode
    {
        if (null === $rawDocNode = $reflection->getDocComment() ?: null) {
            return null;
        }

        $tokens = new TokenIterator($this->phpstanLexer->tokenize($rawDocNode));
        $docNode = $this->phpstanDocParser->parse($tokens);
        $tokens->consumeTokenType(Lexer::TOKEN_END);

        $tagName = $reflection instanceof \ReflectionProperty ? '@var' : '@return';
        $tag = $docNode->getTagsByName($tagName)[0] ?? null;

        if (null === $tag || $tag->value instanceof InvalidTagValueNode) {
            return null;
        }

        return $tag->value->type;
    }
}
