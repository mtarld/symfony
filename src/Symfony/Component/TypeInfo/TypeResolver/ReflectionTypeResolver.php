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

use Symfony\Component\TypeInfo\BuiltinType;
use Symfony\Component\TypeInfo\Exception\InvalidArgumentException;
use Symfony\Component\TypeInfo\Exception\UnsupportedException;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeContext\TypeContext;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class ReflectionTypeResolver implements TypeResolverInterface
{
    /**
     * @var array<class-string, \ReflectionEnum>
     */
    private static array $reflectionEnumCache = [];

    private const UNSUPPORTED_BUILTIN_TYPES = ['void', 'never'];

    public function resolve(mixed $subject, TypeContext $typeContext = null): Type
    {
        if ($subject instanceof \ReflectionUnionType) {
            return Type::union(...array_map(fn (mixed $t): Type => $this->resolve($t, $typeContext), $subject->getTypes()));
        }

        if ($subject instanceof \ReflectionIntersectionType) {
            return Type::intersection(...array_map(fn (mixed $t): Type => $this->resolve($t, $typeContext), $subject->getTypes()));
        }

        if (!$subject instanceof \ReflectionNamedType) {
            throw new UnsupportedException(sprintf('Expected subject to be a "ReflectionNamedType", a "ReflectionUnionType" or a "ReflectionIntersectionType", "%s" given.', get_debug_type($subject)));
        }

        $builtinTypeOrClass = $subject->getName();
        $nullable = $subject->allowsNull();

        if (\in_array($builtinTypeOrClass, self::UNSUPPORTED_BUILTIN_TYPES, true)) {
            throw new UnsupportedException(sprintf('"%s" type is not supported.', $builtinTypeOrClass));
        }

        if (BuiltinType::ARRAY->value === $builtinTypeOrClass) {
            $type = Type::array();

            return $nullable ? Type::nullable($type) : $type;
        }

        if (BuiltinType::ITERABLE->value === $builtinTypeOrClass) {
            $type = Type::iterable();

            return $nullable ? Type::nullable($type) : $type;
        }

        if (\in_array($builtinTypeOrClass, [BuiltinType::MIXED->value, BuiltinType::NULL->value], true)) {
            return Type::builtin($builtinTypeOrClass);
        }

        if ($subject->isBuiltin()) {
            $type = Type::builtin(BuiltinType::from($builtinTypeOrClass));

            return $nullable ? Type::nullable($type) : $type;
        }

        if (\in_array(strtolower($builtinTypeOrClass), ['self', 'static', 'parent'], true) && !$typeContext) {
            throw new InvalidArgumentException(sprintf('A "%s" must be provided to resolve "%s".', TypeContext::class, strtolower($builtinTypeOrClass)));
        }

        /** @var class-string $className */
        $className = match (true) {
            'self' === strtolower($builtinTypeOrClass) => $typeContext->resolveDeclaringClass(),
            'static' === strtolower($builtinTypeOrClass) => $typeContext->resolveCalledClass(),
            'parent' === strtolower($builtinTypeOrClass) => $typeContext->resolveParentClass(),
            default => $builtinTypeOrClass,
        };

        if (is_subclass_of($className, \BackedEnum::class)) {
            $reflectionEnum = (self::$reflectionEnumCache[$className] ??= new \ReflectionEnum($className));
            $backingType = $this->resolve($reflectionEnum->getBackingType(), $typeContext);
            $type = Type::enum($className, $backingType);
        } elseif (is_subclass_of($className, \UnitEnum::class)) {
            $type = Type::enum($className);
        } else {
            $type = Type::object($className);
        }

        return $nullable ? Type::nullable($type) : $type;
    }
}
