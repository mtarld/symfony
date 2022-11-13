<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Template;

use Symfony\Polyfill\Marshaller\Metadata\Type;
use Symfony\Polyfill\Marshaller\Metadata\UnionType;

/**
 * @internal
 */
final class UnionTemplateGenerator
{
    use PhpWriterTrait;
    use VariableNameScoperTrait;

    public function __construct(
        private readonly TemplateGenerator $templateGenerator,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    final public function generate(UnionType $type, string $accessor, array $context): string
    {
        $template = '';
        $typesCount = count($type->types);

        foreach ($this->sortTypesByPriority($type->types) as $i => $type) {
            $ifStructure = sprintf('} elseif (%s) {', $type->validator($accessor));

            if (0 === $i) {
                $ifStructure = sprintf('if (%s) {', $type->validator($accessor));
            } elseif ($typesCount - 1 === $i && !$context['validate_data']) {
                $ifStructure = '} else {';
            }

            $template .= $this->writeLine($ifStructure, $context);
            ++$context['indentation_level'];

            $template .= $this->templateGenerator->generate($type, $accessor, $context);
            --$context['indentation_level'];
        }

        if ($context['validate_data']) {
            $template .= $this->writeLine('} else {', $context);
            ++$context['indentation_level'];

            $template .= $this->writeLine(sprintf("throw new \UnexpectedValueException('Invalid \"%s\" type');", $context['readable_accessor']), $context);
            --$context['indentation_level'];
        }

        $template .= $this->writeLine('}', $context);

        return $template;
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

        $classes = [];
        foreach (array_keys($objectTypes) as $class) {
            $classes[] = ['class' => $class, 'parent' => get_parent_class($class)];
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
            $sortedObjectTypes[] = $objectTypes[$class];
        }

        return array_merge($regularTypes, $sortedObjectTypes);
    }

    /**
     * @param list<array{0: class-string, 1: class-string|false}> $classes
     * @param class-string|false                                  $parentClass
     *
     * @return array<string, array{children: array}>
     */
    private function buildClassTree(array $classes, string|false $parentClass): array
    {
        $branch = [];

        foreach ($classes as $class) {
            if ($class['parent'] === $parentClass) {
                if ([] !== $children = $this->buildClassTree($classes, $class['class'])) {
                    $class['children'] = $children;
                }

                $branch[$class['class']] = $class;
            }
        }

        return $branch;
    }

    /**
     * @param class-string                          $class
     * @param array<string, array{children: array}> $classTree
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
