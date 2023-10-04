<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo\TypeResolver;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprArrayNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFalseNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFloatNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNullNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprTrueNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Symfony\Component\TypeInfo\BuiltinType;
use Symfony\Component\TypeInfo\Exception\InvalidArgumentException;
use Symfony\Component\TypeInfo\Exception\LogicException;
use Symfony\Component\TypeInfo\Exception\UnsupportedException;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\TypeContext\TypeContext;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class StringTypeResolver implements TypeResolverInterface
{
    /**
     * @var array<string, bool>
     */
    private static array $classExistCache = [];

    private readonly Lexer $lexer;
    private readonly TypeParser $parser;

    public function __construct()
    {
        $this->lexer = new Lexer();
        $this->parser = new TypeParser(new ConstExprParser());
    }

    public function resolve(mixed $subject, TypeContext $typeContext = null): Type
    {
        if (!class_exists(TypeParser::class)) {
            throw new LogicException(sprintf('Unable to call "%s()" as the "phpstan/phpdoc-parser" package is not installed. Try running composer require "phpstan/phpdoc-parser".', __METHOD__));
        }

        if (!\is_string($subject)) {
            throw new UnsupportedException(sprintf('Expected subject to be a "string", "%s" given.', get_debug_type($subject)));
        }

        try {
            $tokens = new TokenIterator($this->lexer->tokenize($subject));
            $node = $this->parser->parse($tokens);

            return $this->getTypeFromNode($node, $typeContext);
        } catch (\DomainException $e) {
            throw new UnsupportedException(sprintf('Cannot resolve "%s".', $subject), previous: $e);
        }
    }

    private function getTypeFromNode(TypeNode $node, ?TypeContext $typeContext): Type
    {
        if ($node instanceof CallableTypeNode) {
            return Type::callable();
        }

        if ($node instanceof ArrayTypeNode) {
            return Type::list($this->getTypeFromNode($node->type, $typeContext));
        }

        if ($node instanceof ArrayShapeNode) {
            return Type::array();
        }

        if ($node instanceof ObjectShapeNode) {
            return Type::object();
        }

        if ($node instanceof ThisTypeNode) {
            if (null === $typeContext) {
                throw new InvalidArgumentException(sprintf('A "%s" must be provided to resolve "$this".', TypeContext::class));
            }

            return Type::object($typeContext->resolveCalledClass());
        }

        if ($node instanceof ConstTypeNode) {
            return match ($node->constExpr::class) {
                ConstExprArrayNode::class => Type::array(),
                ConstExprFalseNode::class => Type::false(),
                ConstExprFloatNode::class => Type::float(),
                ConstExprIntegerNode::class => Type::int(),
                ConstExprNullNode::class => Type::null(),
                ConstExprStringNode::class => Type::string(),
                ConstExprTrueNode::class => Type::true(),
                default => throw new \DomainException(sprintf('Unhandled "%s" constant expression.', $node->constExpr::class)),
            };
        }

        if ($node instanceof IdentifierTypeNode) {
            $type = match ($node->name) {
                'bool', 'boolean' => Type::bool(),
                'true' => Type::true(),
                'false' => Type::false(),
                'int', 'integer', 'positive-int', 'negative-int', 'non-positive-int', 'non-negative-int', 'non-zero-int' => Type::int(),
                'float', 'double' => Type::float(),
                'string',
                'class-string',
                'trait-string',
                'interface-string',
                'callable-string',
                'numeric-string',
                'lowercase-string',
                'non-empty-lowercase-string',
                'non-empty-string',
                'non-falsy-string',
                'truthy-string',
                'literal-string',
                'html-escaped-string' => Type::string(),
                'resource' => Type::resource(),
                'object' => Type::object(),
                'callable' => Type::callable(),
                'array', 'non-empty-array' => Type::array(),
                'list', 'non-empty-list' => Type::list(),
                'iterable' => Type::iterable(),
                'mixed' => Type::mixed(),
                'null' => Type::null(),
                'array-key' => Type::union(Type::int(), Type::string()),
                'scalar' => Type::union(Type::int(), Type::float(), Type::string(), Type::bool()),
                'number' => Type::union(Type::int(), Type::float()),
                'numeric' => Type::union(Type::int(), Type::float(), Type::string()),
                'self' => Type::object($typeContext->resolveDeclaringClass()),
                'static' => Type::object($typeContext->resolveCalledClass()),
                'parent' => Type::object($typeContext->resolveParentClass()),
                'void', 'never', 'never-return', 'never-returns', 'no-return' => throw new \DomainException(sprintf('Unhandled "%s" identifier.', $node->name)),
                default => $this->resolveCustomIdentifier($node->name, $typeContext),
            };

            if ($type instanceof ObjectType && \in_array($type->getClassName(), [\Traversable::class, \Iterator::class, \IteratorAggregate::class], true)) {
                return Type::collection($type);
            }

            return $type;
        }

        if ($node instanceof NullableTypeNode) {
            return Type::nullable($this->getTypeFromNode($node->type, $typeContext));
        }

        if ($node instanceof GenericTypeNode) {
            $type = $this->getTypeFromNode($node->type, $typeContext);

            // handle integer ranges as simple integers
            if ($type->isBuiltinType(BuiltinType::INT)) {
                return $type;
            }

            $genericTypes = array_map(fn (TypeNode $t): Type => $this->getTypeFromNode($t, $typeContext), $node->genericTypes);

            if ($type instanceof CollectionType) {
                $keyType = $type->getCollectionKeyType();

                $type = $type->getType();
                if ($type instanceof GenericType) {
                    $type = $type->getType();
                }

                if (1 === \count($genericTypes)) {
                    return Type::collection($type, $genericTypes[0], $keyType);
                } elseif (2 === \count($genericTypes)) {
                    return Type::collection($type, $genericTypes[1], $genericTypes[0]);
                }
            }

            if ($type instanceof ObjectType && \in_array($type->getClassName(), [\Traversable::class, \Iterator::class, \IteratorAggregate::class], true)) {
                if (1 === \count($genericTypes)) {
                    return Type::collection($type, $genericTypes[0], null);
                } elseif (2 === \count($genericTypes)) {
                    return Type::collection($type, $genericTypes[1], $genericTypes[0]);
                }

                return Type::collection($type);
            }

            return Type::generic($type, ...$genericTypes);
        }

        if ($node instanceof UnionTypeNode) {
            return Type::union(...array_map(fn (TypeNode $t): Type => $this->getTypeFromNode($t, $typeContext), $node->types));
        }

        if ($node instanceof IntersectionTypeNode) {
            return Type::intersection(...array_map(fn (TypeNode $t): Type => $this->getTypeFromNode($t, $typeContext), $node->types));
        }

        throw new \DomainException(sprintf('Unhandled "%s" node.', $node::class));
    }

    private function resolveCustomIdentifier(string $identifier, ?TypeContext $typeContext): Type
    {
        $classNameOrTemplate = $typeContext ? $typeContext->resolve($identifier) : $identifier;

        if (isset(self::$classExistCache[$classNameOrTemplate])) {
            return self::$classExistCache[$classNameOrTemplate] ? Type::object($classNameOrTemplate) : Type::template($classNameOrTemplate);
        }

        if (class_exists($classNameOrTemplate) || interface_exists($classNameOrTemplate)) {
            self::$classExistCache[$classNameOrTemplate] = true;

            return Type::object($classNameOrTemplate);
        }

        try {
            new \ReflectionClass($classNameOrTemplate);
            self::$classExistCache[$classNameOrTemplate] = true;

            return Type::object($classNameOrTemplate);
        } catch (\Throwable) {
        }

        self::$classExistCache[$classNameOrTemplate] = false;

        return Type::template($classNameOrTemplate);
    }
}
