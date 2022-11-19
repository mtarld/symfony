<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;

/**
 * @internal
 */
final class PhpstanTypeHelper
{
    /**
     * @param class-string $class
     */
    public function getType(TypeNode $node, string $class): string
    {
        return $this->extractType($node, TypeNameResolver::createForClass($class));
    }

    private function extractType(TypeNode $node, TypeNameResolver $nameResolver): string
    {
        return match (get_class($node)) {
            UnionTypeNode::class => $this->extractUnionType($node, $nameResolver),
            IdentifierTypeNode::class => $this->extractIdentifierType($node, $nameResolver),
            GenericTypeNode::class => $this->extractGenericType($node, $nameResolver),
            ArrayTypeNode::class, ArrayShapeNode::class => $this->extractArrayType($node, $nameResolver),
            NullableTypeNode::class => sprintf('?%s', $this->extractType($node->type, $nameResolver)),
            ThisTypeNode::class => $nameResolver->resolveRootClass(),
            CallableTypeNode::class => 'callable',
            IntersectionTypeNode::class => throw new \LogicException('Cannot handle intersection types.'),
        };
    }

    private function extractUnionType(UnionTypeNode $node, TypeNameResolver $nameResolver): string
    {
        $nullable = false;
        $typeStrings = [];

        foreach ($node->types as $type) {
            $typeString = $this->extractType($type, $nameResolver);

            if (str_starts_with($typeString, '?')) {
                $nullable = true;
                $typeString = substr($typeString, 1);
            }

            $typeStrings[] = $typeString;
        }

        if ($nullable && !in_array('null', $typeStrings)) {
            $typeStrings[] = 'null';
        }

        return implode('|', $typeStrings);
    }

    private function extractIdentifierType(IdentifierTypeNode $node, TypeNameResolver $nameResolver): string
    {
        $type = match ($node->name) {
            'bool', 'boolean', 'true', 'false' => 'bool',
            'int', 'integer' => 'int',
            'float' => 'float',
            'string' => 'string',
            'resource' => 'resource',
            'object' => 'object',
            'callable' => 'callable',
            'array', 'list', 'iterable', 'non-empty-array', 'non-empty-list' => 'array',
            'mixed' => 'mixed',
            'null' => 'null',
            'static', 'self' => $nameResolver->resolveRootClass(),
            'parent' => $nameResolver->resolveParentClass(),
            default => null,
        };

        if (null === $type) {
            $type = $nameResolver->resolve($node->name);
        }

        return $type;
    }

    private function extractGenericType(GenericTypeNode $node, TypeNameResolver $nameResolver): string
    {
        if ('array' === $mainType = $this->extractType($node->type, $nameResolver)) {
            $keyType = 'int';
            $valueType = $this->extractType($node->genericTypes[0], $nameResolver);
            if (2 === \count($node->genericTypes)) {
                $keyType = $valueType;
                $valueType = $this->extractType($node->genericTypes[1], $nameResolver);
            }

            return sprintf('array<%s, %s>', $keyType, $valueType);
        }

        throw new \LogicException(sprintf('Unhandled "%s" generic type', (string) $node));
    }

    private function extractArrayType(ArrayTypeNode|ArrayShapeNode $node, TypeNameResolver $nameResolver): string
    {
        if ($node instanceof ArrayTypeNode) {
            return sprintf('array<int, %s>', $this->extractType($node->type, $nameResolver));
        }

        if ([] === $items = $node->items) {
            return 'array<string, mixed>';
        }

        $valueType = $node->items[0]->valueType;

        if (\count($items) > 1) {
            $valueType = new UnionTypeNode(array_map(fn (ArrayShapeItemNode $i): TypeNode => $i->valueType, $node->items));
        }

        return sprintf('array<string, %s>', $this->extractType($valueType));
    }
}
