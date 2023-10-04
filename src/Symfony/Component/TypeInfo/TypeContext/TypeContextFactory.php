<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo\TypeContext;

use phpDocumentor\Reflection\Types\ContextFactory;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Symfony\Component\TypeInfo\Exception\RuntimeException;
use Symfony\Component\TypeInfo\Exception\UnsupportedException;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeResolver\StringTypeResolver;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class TypeContextFactory
{
    /**
     * @var array<class-string, \ReflectionClass>
     */
    private static array $reflectionClassCache = [];

    private ?ContextFactory $phpDocumentorContextFactory = null;
    private ?Lexer $phpstanLexer = null;
    private ?PhpDocParser $phpstanParser = null;

    public function __construct(
        private readonly ?StringTypeResolver $stringTypeResolver = null,
    ) {
    }

    public function createFromClassName(string $calledClassName, string $declaringClassName = null): TypeContext
    {
        if (!class_exists(ContextFactory::class)) {
            throw new \LogicException(sprintf('Unable to call "%s()" as the "phpdocumentor/type-resolver" package is not installed. Try running composer require "phpdocumentor/type-resolver".', __METHOD__));
        }

        $declaringClassName ??= $calledClassName;

        $calledClassPath = explode('\\', $calledClassName);
        $declaringClassPath = explode('\\', $declaringClassName);

        $declaringClassReflection = (self::$reflectionClassCache[$declaringClassName] ??= new \ReflectionClass($declaringClassName));

        $typeContext = new TypeContext(
            array_pop($calledClassPath),
            array_pop($declaringClassPath),
            trim($declaringClassReflection->getNamespaceName(), '\\'),
            $this->collectUses($declaringClassReflection),
        );

        return new TypeContext(
            $typeContext->calledClassName,
            $typeContext->declaringClassName,
            $typeContext->namespace,
            $typeContext->uses,
            $this->collectClassTemplates($declaringClassReflection, $typeContext),
        );
    }

    public function createFromReflection(\Reflector $reflection): ?TypeContext
    {
        $declaringClassReflection = match (true) {
            $reflection instanceof \ReflectionClass => $reflection,
            $reflection instanceof \ReflectionMethod => $reflection->getDeclaringClass(),
            $reflection instanceof \ReflectionProperty => $reflection->getDeclaringClass(),
            $reflection instanceof \ReflectionParameter => $reflection->getDeclaringClass(),
            $reflection instanceof \ReflectionFunctionAbstract => $reflection->getClosureScopeClass(),
            default => null,
        };

        if (null === $declaringClassReflection) {
            return null;
        }

        $typeContext = new TypeContext(
            $declaringClassReflection->getShortName(),
            $declaringClassReflection->getShortName(),
            $declaringClassReflection->getNamespaceName(),
            $this->collectUses($declaringClassReflection),
        );

        return new TypeContext(
            $typeContext->calledClassName,
            $typeContext->declaringClassName,
            $typeContext->namespace,
            $typeContext->uses,
            $this->collectClassTemplates($declaringClassReflection, $typeContext),
        );
    }

    /**
     * @return array<string, string>
     */
    private function collectUses(\ReflectionClass $reflection): array
    {
        $fileName = $reflection->getFileName();
        if (!\is_string($fileName) || !is_file($fileName)) {
            return [];
        }

        if (false === $contents = @file_get_contents($fileName)) {
            throw new RuntimeException(sprintf('Unable to read file "%s".', $fileName));
        }

        $traitUses = [];
        foreach ($reflection->getTraits() as $traitReflection) {
            $traitUses[] = $this->collectUses($traitReflection);
        }

        $uses = array_merge(...$traitUses);

        $this->phpDocumentorContextFactory ??= new ContextFactory();
        $context = $this->phpDocumentorContextFactory->createForNamespace($reflection->getNamespaceName(), $contents);

        return array_merge($context->getNamespaceAliases(), ...$traitUses);
    }

    /**
     * @return list<array{name: string, type: ?Type}>
     */
    private function collectClassTemplates(\ReflectionClass $reflection, TypeContext $typeContext): array
    {
        if (!$this->stringTypeResolver || !class_exists(PhpDocParser::class)) {
            return [];
        }

        if (!$rawDocNode = $reflection->getDocComment()) {
            return [];
        }

        $this->phpstanLexer ??= new Lexer();
        $this->phpstanParser ??= new PhpDocParser(new TypeParser(new ConstExprParser()), new ConstExprParser());

        $tokens = new TokenIterator($this->phpstanLexer->tokenize($rawDocNode));

        $templates = [];
        foreach ($this->phpstanParser->parse($tokens)->getTagsByName('@template') as $tag) {
            if (!$tag->value instanceof TemplateTagValueNode) {
                continue;
            }

            $type = null;
            $typeString = ((string) $tag->value->bound) ?: null;

            try {
                if (null !== $typeString) {
                    $type = $this->stringTypeResolver->resolve($typeString);
                }
            } catch (UnsupportedException) {
            }

            $templates[] = ['name' => $tag->value->name, 'type' => $type];
        }

        return $templates;
    }
}
