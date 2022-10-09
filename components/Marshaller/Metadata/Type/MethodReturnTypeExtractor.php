<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Metadata\Type;

use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\ContextFactory;
use Symfony\Component\PropertyInfo\Util\PhpDocTypeHelper;

/**
 * This has been pulled and adapted from the PropertyInfo component.
 * It must be moved and improved.
 */
final class MethodReturnTypeExtractor
{
    public function __construct(
        private readonly TypeFactory $typeFactory,
    ) {
    }

    public function extract(string $class, string $method): Type
    {
        if (null !== $type = $this->extractFromPhpDoc($class, $method)) {
            return $type;
        }

        if (null !== $type = $this->extractFromReflection($class, $method)) {
            return $type;
        }

        throw new \RuntimeException(sprintf('Cannot find any type for method %s::%s()', $class, $method));
    }

    private function extractFromPhpDoc(string $class, string $method): ?Type
    {
        $reflectionMethod = new \ReflectionMethod($class, $method);

        try {
            $docBlock = DocBlockFactory::createInstance()->create(
                $reflectionMethod,
                (new ContextFactory())->createFromReflector($reflectionMethod->getDeclaringClass()),
            );
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return null;
        }

        $parentClass = null;
        $types = [];

        $tag = $docBlock->getTagsByName('return')[0] ?? null;
        if (!$tag instanceof Return_ || null === $tag->getType()) {
            return null;
        }

        $type = (new PhpDocTypeHelper())->getTypes($tag->getType())[0] ?? null;
        if (null === $type) {
            return null;
        }

        return $this->typeFactory->fromPropertyInfoType($type);
    }

    private function extractFromReflection(string $class, string $method): ?Type
    {
        try {
            $reflectionMethod = new \ReflectionMethod($class, $method);

            /** @var \ReflectionNamedType $reflectionType */
            $reflectionType = $reflectionMethod->getReturnType();

            if (null === $reflectionType) {
                return null;
            }

            $nullable = $reflectionType->allowsNull();

            $phpTypeOrClass = $reflectionType->getName();
            if ('null' === $phpTypeOrClass || 'mixed' === $phpTypeOrClass || 'never' === $phpTypeOrClass || 'void' === $phpTypeOrClass) {
                return null;
            }

            if ('array' === $phpTypeOrClass) {
                return new Type('array', $nullable);
            } elseif ($reflectionType->isBuiltin()) {
                return new Type($phpTypeOrClass, $nullable);
            } else {
                $className = $phpTypeOrClass;
                $declaringClass = $reflectionMethod->getDeclaringClass();

                if ('self' === $lcName = strtolower($name)) {
                    $className = $declaringClass->name;
                }

                if ('parent' === $lcName && $parent = $declaringClass->getParentClass()) {
                    return $parent->name;
                }

                return new Type('object', $nullable, $className);
            }
        } catch (\ReflectionException $e) {
            return null;
        }
    }
}
