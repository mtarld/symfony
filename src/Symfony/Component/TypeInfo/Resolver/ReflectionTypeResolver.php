<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo\Resolver;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\TypeInfo\Exception\UnsupportedException;
use Symfony\Component\TypeInfo\IntersectionType;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\UnionType;

/**
 * DO NOT REVIEW, this is part of another upcoming PR (work still in progress)
 */
final readonly class ReflectionTypeResolver implements TypeResolverInterface
{
    private const UNSUPPORTED_BUILTIN_TYPES = ['void', 'never'];

    public function __construct(
        private ?CacheItemPoolInterface $enumBackingTypeCache = null,
    ) {
    }

    public function resolve(mixed $subject, \ReflectionClass $declaringClass = null): Type
    {
        if (!$subject instanceof \ReflectionNamedType) {
            throw new UnsupportedException(sprintf('Expected subject to be a "ReflectionNamedType", "%s" given.', get_debug_type($subject)));
        }

        if ($subject instanceof \ReflectionIntersectionType) {
            return new IntersectionType(...array_map($this->resolve(...), $subject->getTypes()));
        }

        if ($subject instanceof \ReflectionUnionType) {
            return new UnionType(...array_map($this->resolve(...), $subject->getTypes()));
        }

        $builtinTypeOrClass = $subject->getName();
        $nullable = $subject->allowsNull();

        if (\in_array($builtinTypeOrClass, self::UNSUPPORTED_BUILTIN_TYPES, true)) {
            throw new UnsupportedException(sprintf('"%s" type is not supported.', $builtinTypeOrClass));
        }

        if (Type::BUILTIN_TYPE_ARRAY === $builtinTypeOrClass) {
            return Type::array(nullable: $nullable);
        }

        if (Type::BUILTIN_TYPE_ITERABLE === $builtinTypeOrClass) {
            return Type::iterable(nullable: $nullable);
        }

        if ($subject->isBuiltin()) {
            return Type::builtin($builtinTypeOrClass, nullable: $nullable);
        }

        /** @var class-string $className */
        $className = match (true) {
            // TODO cannot find a way to narrow static here
            $declaringClass && \in_array(strtolower($builtinTypeOrClass), ['self', 'static'], true) => $declaringClass->name,
            $declaringClass && 'parent' === strtolower($builtinTypeOrClass) && $parent = $declaringClass->getParentClass() => $parent->name,
            default => $builtinTypeOrClass,
        };

        if (!class_exists($className) && !interface_exists($className)) {
            throw new UnsupportedException(sprintf('"%s" type is invalid.', $className));
        }

        if (is_subclass_of($className, \BackedEnum::class)) {
            if ($this->enumBackingTypeCache) {
                $item = $this->enumBackingTypeCache->getItem(rawurlencode($className));
                if ($item->isHit()) {
                    return Type::enum($className, $item->get(), $nullable);
                }
            }

            $backingType = $this->resolve((new \ReflectionEnum($className))->getBackingType(), $declaringClass);

            if (isset($item)) {
                $this->enumBackingTypeCache->save($item->set($backingType));
            }

            return Type::enum($className, $backingType, $nullable);
        }

        return Type::object($className, $nullable);
    }
}
