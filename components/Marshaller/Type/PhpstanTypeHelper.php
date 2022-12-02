<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
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
     * @param class-string               $class
     * @param list<TemplateTagValueNode> $templateNodes
     */
    public function getType(TypeNode $typeNode, string $class, array $templateNodes): string
    {
        $templateNodeNames = array_map(fn (TemplateTagValueNode $t): string => $t->name, $templateNodes);

        return $this->extractType($typeNode, TypeNameResolver::createForClass($class, $templateNodeNames));
    }

    private function extractType(TypeNode $node, TypeNameResolver $nameResolver): string
    {
        if ($node instanceof UnionTypeNode) {
            return implode('|', array_map(fn (TypeNode $t): string => $this->extractType($t, $nameResolver), $node->types));
        }

        if ($node instanceof NullableTypeNode) {
            return sprintf('?%s', $this->extractType($node->type, $nameResolver));
        }

        if ($node instanceof IdentifierTypeNode) {
            return $this->extractIdentifierType($node, $nameResolver);
        }

        if ($node instanceof GenericTypeNode) {
            return $this->extractGenericType($node, $nameResolver);
        }

        if ($node instanceof ArrayTypeNode || $node instanceof ArrayShapeNode) {
            return $this->extractArrayType($node, $nameResolver);
        }

        if ($node instanceof ThisTypeNode) {
            return $nameResolver->resolveRootClass();
        }

        if ($node instanceof CallableTypeNode) {
            return 'callable';
        }

        if ($node instanceof IntersectionTypeNode) {
            throw new \LogicException('Cannot handle intersection types.');
        }

        // TODO test me
        throw new \InvalidArgumentException(sprintf('Unhandled "%s" node.', get_class($node)));
    }

    private function extractIdentifierType(IdentifierTypeNode $node, TypeNameResolver $nameResolver): string
    {
        return match ($node->name) {
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
            default => $nameResolver->resolve($node->name),
        };
    }

    private function extractGenericType(GenericTypeNode $node, TypeNameResolver $nameResolver): string
    {
        $genericParameters = array_map(fn (TypeNode $t): string => $this->extractType($t, $nameResolver), $node->genericTypes);

        if ('array' === $mainType = $this->extractType($node->type, $nameResolver)) {
            $keyType = 'int';
            $valueType = $genericParameters[0];
            if (2 === \count($genericParameters)) {
                $keyType = $valueType;
                $valueType = $genericParameters[1];
            }

            $genericParameters = [$keyType, $valueType];
        }

        return sprintf('%s<%s>', $mainType, implode(', ', $genericParameters));
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

        return sprintf('array<string, %s>', $this->extractType($valueType, $nameResolver));
    }
}
