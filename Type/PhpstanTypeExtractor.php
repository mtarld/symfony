<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Type;

use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
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
        if (null === $typeNode = $this->getTypeNode($property)) {
            return $this->decoratedTypeExtractor->extractFromProperty($property);
        }

        return $this->phpstanTypeHelper->getType($typeNode, $property->getDeclaringClass()->getName(), $this->getTemplateNodes($property->getDeclaringClass()));
    }

    public function extractFromReturnType(\ReflectionFunctionAbstract $function): string
    {
        if (null === $typeNode = $this->getTypeNode($function)) {
            return $this->decoratedTypeExtractor->extractFromReturnType($function);
        }

        $declaringClass = $function instanceof \ReflectionMethod ? $function->getDeclaringClass() : $function->getClosureScopeClass();
        if (null === $declaringClass) {
            throw new \InvalidArgumentException(sprintf('Cannot find class related to "%s()".', $function->getName()));
        }

        return $this->phpstanTypeHelper->getType($typeNode, $declaringClass->getName(), $this->getTemplateNodes($declaringClass));
    }

    public function extractFromParameter(\ReflectionParameter $parameter): string
    {
        if (null === $typeNode = $this->getTypeNode($parameter)) {
            return $this->decoratedTypeExtractor->extractFromParameter($parameter);
        }

        $function = $parameter->getDeclaringFunction();
        $declaringClass = $function instanceof \ReflectionMethod ? $function->getDeclaringClass() : $function->getClosureScopeClass();

        if (null === $declaringClass) {
            throw new \InvalidArgumentException(sprintf('Cannot find class related to "%s()".', $function->getName()));
        }

        return $this->phpstanTypeHelper->getType($typeNode, $declaringClass->getName(), $this->getTemplateNodes($declaringClass));
    }

    private function getTypeNode(\ReflectionProperty|\ReflectionFunctionAbstract|\ReflectionParameter $reflection): ?TypeNode
    {
        $rawDocNode = $reflection instanceof \ReflectionParameter ? $reflection->getDeclaringFunction()->getDocComment() : $reflection->getDocComment();
        if (!$rawDocNode) {
            return null;
        }

        $tokens = new TokenIterator($this->phpstanLexer->tokenize($rawDocNode));
        $docNode = $this->phpstanDocParser->parse($tokens);
        $tokens->consumeTokenType(Lexer::TOKEN_END);

        $tagName = match (true) {
            $reflection instanceof \ReflectionProperty => '@var',
            $reflection instanceof \ReflectionFunctionAbstract => '@return',
            $reflection instanceof \ReflectionParameter => '@param',
        };

        $tags = $docNode->getTagsByName($tagName);
        $tag = reset($tags) ?: null;

        /** @var VarTagValueNode|ReturnTagValueNode|ParamTagValueNode|InvalidTagValueNode|null $tagValue */
        $tagValue = $tag?->value;

        if (null === $tagValue || $tagValue instanceof InvalidTagValueNode) {
            return null;
        }

        return $tagValue->type;
    }

    /**
     * @param \ReflectionClass<object> $reflection
     *
     * @return list<TemplateTagValueNode>
     */
    private function getTemplateNodes(\ReflectionClass $reflection): array
    {
        if (null === $rawDocNode = $reflection->getDocComment() ?: null) {
            return [];
        }

        $tokens = new TokenIterator($this->phpstanLexer->tokenize($rawDocNode));
        $docNode = $this->phpstanDocParser->parse($tokens);
        $tokens->consumeTokenType(Lexer::TOKEN_END);

        $tags = $docNode->getTagsByName('@template');

        return array_values(array_filter(
            array_map(fn (PhpDocTagNode $t): PhpDocTagValueNode => $t->value, $tags),
            fn (PhpDocTagValueNode $v): bool => $v instanceof TemplateTagValueNode,
        ));
    }

    public function extractTemplateFromClass(\ReflectionClass $class): array
    {
        $templates = array_map(fn (TemplateTagValueNode $t): string => $t->name, $this->getTemplateNodes($class));
        if (!array_unique($templates)) {
            throw new \InvalidArgumentException(sprintf('Templates defined in "%s" must be unique.', $class->getName()));
        }

        return $templates;
    }
}
