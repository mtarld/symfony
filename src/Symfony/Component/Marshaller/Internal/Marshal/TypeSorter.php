<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Marshal;

use Symfony\Component\Marshaller\Exception\LogicException;
use Symfony\Component\Marshaller\Internal\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 *
 * @psalm-type PhoneType = array{phone: string}
 */
final class TypeSorter
{
    /**
     * @param list<Type> $types
     *
     * @return list<Type>
     */
    public function sortByPrecision(array $types): array
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
            throw new LogicException('Found several classes at the same hierarchy level.');
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
     * @param class-string                                                                                                            $class
     * @param array<class-string, array{class: class-string, children?: array{class: class-string, children?: array<string, mixed>}}> $classTree
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
