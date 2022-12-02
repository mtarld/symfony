<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template;

use Symfony\Component\Marshaller\Native\Ast\Node\IfNode;
use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Native\Type\Type;
use Symfony\Component\Marshaller\Native\Type\UnionType;

/**
 * @internal
 */
final class UnionTemplateGenerator
{
    use VariableNameScoperTrait;

    public function __construct(
        private readonly TemplateGeneratorInterface $templateGenerator,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    public function generate(UnionType $type, NodeInterface $accessor, array $context): array
    {
        if (\count($type->types) <= 0) {
            return [];
        }

        $types = $this->sortTypesByPriority($type->types);

        if (1 === \count($types)) {
            return $this->templateGenerator->generate($types[0], $accessor, $context);
        }

        /** @var Type $ifType */
        $ifType = array_shift($types);

        /** @var Type $elseType */
        $elseType = array_pop($types);

        /** @var list<array{condition: NodeInterface, body: list<NodeInterface>}> $elseIfTypes */
        $elseIfTypes = array_map(fn (Type $t): array => ['condition' => $t->validator($accessor), 'body' => $this->templateGenerator->generate($t, $accessor, $context)], $types);

        return [new IfNode(
            $ifType->validator($accessor),
            $this->templateGenerator->generate($ifType, $accessor, $context),
            $this->templateGenerator->generate($elseType, $accessor, $context),
            $elseIfTypes,
        )];
    }

    /**
     * @param list<Type> $types
     *
     * @return list<Type>
     */
    private function sortTypesByPriority(array $types): array
    {
        $regularTypes = array_values(array_filter($types, fn (Type $t): bool => !$t->isObject()));

        $objectTypes = [];
        foreach (array_diff($types, $regularTypes) as $objectType) {
            $objectTypes[$objectType->className()] = $objectType;
        }

        /** @var array<class-string, array{class: class-string, parent?: class-string|false}> $classes */
        $classes = [];
        $previousClass = null;
        foreach (array_keys($objectTypes) as $class) {
            $classOrParent = $class;

            while ($classOrParent) {
                $classes[$classOrParent] = ['class' => $classOrParent];
                if ($previousClass && $previousClass !== $classOrParent) {
                    $classes[$previousClass]['parent'] = $classOrParent;
                }

                $previousClass = $classOrParent;
                $classOrParent = get_parent_class($classOrParent);
            }
        }

        $classTree = $this->buildClassTree($classes, false);

        $classDepths = [];
        foreach ($classes as $class) {
            $classDepths[$class['class']] = $this->depth($class['class'], $classTree);
        }

        if (\count(array_unique($classDepths)) !== \count($classDepths)) {
            throw new \RuntimeException('Found several classes at the same hierarchy level.');
        }

        arsort($classDepths);

        $sortedObjectTypes = [];
        foreach (array_keys($classDepths) as $class) {
            if (isset($objectTypes[$class])) {
                $sortedObjectTypes[] = $objectTypes[$class];
            }
        }

        return array_unique(array_merge($regularTypes, $sortedObjectTypes));
    }

    /**
     * @param array<class-string, array{class: class-string, parent?: class-string|false}> $classes
     * @param class-string|false                                                           $parentClass
     *
     * @return array<class-string, array{class: class-string, parent?: class-string|false, children?: array<string, mixed>}>
     */
    private function buildClassTree(array $classes, string|false $parentClass): array
    {
        $branch = [];

        foreach ($classes as $class) {
            if (($class['parent'] ?? false) === $parentClass) {
                if ([] !== $children = $this->buildClassTree($classes, $class['class'])) {
                    $class['children'] = $children;
                }

                $branch[$class['class']] = $class;
            }
        }

        return $branch;
    }

    /**
     * @param class-string                                                               $class
     * @param array<string, array{class: class-string, children?: array<string, mixed>}> $classTree
     */
    private function depth(string $class, array $classTree, int $depth = 0): int
    {
        foreach ($classTree as $leaf) {
            if ($leaf['class'] === $class) {
                return $depth;
            }
        }

        $maxDepth = $depth;
        foreach ($classTree as $branch) {
            $maxDepth = max($this->depth($class, $branch['children'] ?? [], $depth + 1), $maxDepth);
        }

        return $maxDepth;
    }
}
