<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo\Context;

use Symfony\Component\Encoder\Exception\InvalidArgumentException;
use Symfony\Component\TypeInfo\Context\TemplateExtractor;
use Symfony\Component\TypeInfo\GenericType;
use Symfony\Component\TypeInfo\IntersectionType;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\UnionType;

/**
 * DO NOT REVIEW, this is part of another upcoming PR (work still in progress)
 */
final readonly class TypeGenericsHelper
{
    private TemplateExtractor $templateExtractor;

    public function __construct()
    {
        $this->templateExtractor = new TemplateExtractor();
    }

    /**
     * @param class-string $className
     *
     * @return array<string, Type>
     */
    public function getClassGenericTypes(string $className, Type $type): array
    {
        $findClassType = static function (string $className, Type $type) use (&$findClassType): ?Type {
            if ($type->isObject() && $type->getClassName() === $className) {
                return $type;
            }

            if ($type instanceof UnionType) {
                foreach ($type->getTypes() as $unionType) {
                    if (null !== $t = $findClassType($className, $unionType)) {
                        return $t;
                    }
                }
            }

            if ($type instanceof IntersectionType) {
                foreach ($type->getTypes() as $intersectionType) {
                    if (null !== $t = $findClassType($className, $intersectionType)) {
                        return $t;
                    }
                }
            }

            if ($type instanceof GenericType) {
                foreach ($type->getGenericTypes() as $genericType) {
                    if (null !== $t = $findClassType($className, $genericType)) {
                        return $t;
                    }
                }
            }


            return null;
        };

        if (null === $classType = $findClassType($className, $type)) {
            return [];
        }


        $genericTypes = $classType instanceof GenericType ? $classType->getGenericTypes() : [];
        $templates = $this->templateExtractor->getTemplates(new \ReflectionClass($className));

        if (\count($templates) !== \count($genericTypes)) {
            throw new InvalidArgumentException(sprintf('Given %d generic parameters in "%s", but %d templates are defined in "%2$s".', \count($genericTypes), $className, \count($templates)));
        }

        $classGenericTypes = [];
        foreach ($genericTypes as $i => $genericType) {
            $classGenericTypes[$templates[$i]] = $genericType;
        }

        return $classGenericTypes;
    }

    /**
     * @param array<string, Type> $genericTypes
     */
    public function replaceGenericTypes(Type $type, array $genericTypes): Type
    {
        if (isset($genericTypes[(string) $type])) {
            return $genericTypes[(string) $type];
        }

        if ($type instanceof UnionType) {
            $unionTypes = array_map(fn (Type $t): Type => $this->replaceGenericTypes($t, $genericTypes), $type->getTypes());

            return new UnionType(...$unionTypes);
        }

        if ($type instanceof IntersectionType) {
            $intersectionTypes = array_map(fn (Type $t): Type => $this->replaceGenericTypes($t, $genericTypes), $type->getTypes());

            return new IntersectionType(...$intersectionTypes);
        }

        if ($type instanceof GenericType) {
            $genericTypes = array_map(fn (Type $t): Type => $this->replaceGenericTypes($t, $genericTypes), $type->getGenericTypes());

            return new GenericType($type->getMainType(), ...$genericTypes);
        }

        return $type;
    }
}
