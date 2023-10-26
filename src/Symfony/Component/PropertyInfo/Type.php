<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\PropertyInfo;

use Symfony\Component\PropertyInfo\Util\BackwardCompatibilityHelper;
use Symfony\Component\TypeInfo\Exception\InvalidArgumentException;
use Symfony\Component\TypeInfo\Type as TypeInfoType;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\TypeIdentifier;

trigger_deprecation('symfony/property-info', '7.1', 'The "%s" class is deprecated. Use "%s" of "symfony/type-info" component instead.', Type::class, TypeInfoType::class);

/**
 * Type value object (immutable).
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @deprecated since Symfony 7.1, use "Symfony\Component\TypeInfo\Type" of "symfony/type-info" component instead.
 *
 * @final
 */
class Type
{
    public const BUILTIN_TYPE_INT = TypeIdentifier::INT->value;
    public const BUILTIN_TYPE_FLOAT = TypeIdentifier::FLOAT->value;
    public const BUILTIN_TYPE_STRING = TypeIdentifier::STRING->value;
    public const BUILTIN_TYPE_BOOL = TypeIdentifier::BOOL->value;
    public const BUILTIN_TYPE_RESOURCE = TypeIdentifier::RESOURCE->value;
    public const BUILTIN_TYPE_OBJECT = TypeIdentifier::OBJECT->value;
    public const BUILTIN_TYPE_ARRAY = TypeIdentifier::ARRAY->value;
    public const BUILTIN_TYPE_NULL = TypeIdentifier::NULL->value;
    public const BUILTIN_TYPE_FALSE = TypeIdentifier::FALSE->value;
    public const BUILTIN_TYPE_TRUE = TypeIdentifier::TRUE->value;
    public const BUILTIN_TYPE_CALLABLE = TypeIdentifier::CALLABLE->value;
    public const BUILTIN_TYPE_ITERABLE = TypeIdentifier::ITERABLE->value;

    /**
     * List of PHP builtin types.
     *
     * @var string[]
     */
    public static array $builtinTypes = [
        self::BUILTIN_TYPE_INT,
        self::BUILTIN_TYPE_FLOAT,
        self::BUILTIN_TYPE_STRING,
        self::BUILTIN_TYPE_BOOL,
        self::BUILTIN_TYPE_RESOURCE,
        self::BUILTIN_TYPE_OBJECT,
        self::BUILTIN_TYPE_ARRAY,
        self::BUILTIN_TYPE_CALLABLE,
        self::BUILTIN_TYPE_FALSE,
        self::BUILTIN_TYPE_TRUE,
        self::BUILTIN_TYPE_NULL,
        self::BUILTIN_TYPE_ITERABLE,
    ];

    /**
     * List of PHP builtin collection types.
     *
     * @var string[]
     */
    public static array $builtinCollectionTypes = [
        self::BUILTIN_TYPE_ARRAY,
        self::BUILTIN_TYPE_ITERABLE,
    ];

    /**
     * @internal
     */
    public TypeInfoType $internalType;

    /**
     * @param Type[]|Type|null $collectionKeyType
     * @param Type[]|Type|null $collectionValueType
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(string $builtinType, bool $nullable = false, ?string $class = null, bool $collection = false, array|self|null $collectionKeyType = null, array|self|null $collectionValueType = null)
    {
        $typeIdentifier = $builtinType;
        $variableTypes = [];

        $collectionKeyType = $this->validateCollectionArgument($collectionKeyType, 5, '$collectionKeyType') ?? [];
        $collectionValueType = $this->validateCollectionArgument($collectionValueType, 6, '$collectionValueType') ?? [];

        if ($collectionKeyType) {
            if (\is_array($collectionKeyType)) {
                $collectionKeyType = array_unique(array_map(fn ($t): TypeInfoType => $t->internalType, $collectionKeyType));
                $variableTypes[] = \count($collectionKeyType) > 1 ? TypeInfoType::union(...$collectionKeyType) : $collectionKeyType[0];
            } else {
                $variableTypes[] = $collectionKeyType->internalType;
            }
        }

        if ($collectionValueType) {
            if (!$collectionKeyType) {
                $variableTypes[] = [] === $collectionKeyType ? TypeInfoType::mixed() : TypeInfoType::union(TypeInfoType::int(), TypeInfoType::string());
            }

            if (\is_array($collectionValueType)) {
                $collectionValueType = array_unique(array_map(fn ($t): TypeInfoType => $t->internalType, $collectionValueType));
                $variableTypes[] = \count($collectionValueType) > 1 ? TypeInfoType::union(...$collectionValueType) : $collectionValueType[0];
            } else {
                $variableTypes[] = $collectionValueType->internalType;
            }
        }

        if ($collectionKeyType && !$collectionValueType) {
            $variableTypes[] = TypeInfoType::mixed();
        }

        try {
            $this->internalType = null !== $class ? TypeInfoType::object($class) : TypeInfoType::builtin(TypeIdentifier::from($typeIdentifier));
        } catch (\ValueError) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid PHP type.', $typeIdentifier));
        }

        if (\count($variableTypes)) {
            $this->internalType = TypeInfoType::generic($this->internalType, ...$variableTypes);
        }

        if ($nullable && !$this->internalType->isNullable) {
            $this->internalType = TypeInfoType::nullable($this->internalType);
        }

        $this->internalType->setCollection($collection);
    }

    /**
     * Gets built-in type.
     *
     * Can be bool, int, float, string, array, object, resource, null, callback or iterable.
     */
    public function getBuiltinType(): string
    {
        $internalType = BackwardCompatibilityHelper::unwrapNullableType($this->internalType);

        return $internalType->getBaseType()->getTypeIdentifier()->value;
    }

    public function isNullable(): bool
    {
        return $this->internalType->isNullable();
    }

    /**
     * Gets the class name.
     *
     * Only applicable if the built-in type is object.
     */
    public function getClassName(): ?string
    {
        $internalType = BackwardCompatibilityHelper::unwrapNullableType($this->internalType);
        $internalType = $internalType->getBaseType();

        if (!$internalType instanceof ObjectType) {
            return null;
        }

        return $internalType->getClassName();
    }

    public function isCollection(): bool
    {
        return $this->internalType->isCollection;
    }

    /**
     * Gets collection key types.
     *
     * Only applicable for a collection type.
     *
     * @return Type[]
     */
    public function getCollectionKeyTypes(): array
    {
        $internalType = BackwardCompatibilityHelper::unwrapNullableType($this->internalType);

        if (!$internalType instanceof GenericType) {
            return [];
        }

        if (null === ($collectionKeyType = $internalType->getVariableTypes()[0] ?? null)) {
            return [];
        }

        return BackwardCompatibilityHelper::convertTypeToLegacyTypes($collectionKeyType) ?? [];
    }

    /**
     * Gets collection value types.
     *
     * Only applicable for a collection type.
     *
     * @return Type[]
     */
    public function getCollectionValueTypes(): array
    {
        $internalType = BackwardCompatibilityHelper::unwrapNullableType($this->internalType);

        if (!$internalType instanceof GenericType) {
            return [];
        }

        if (null === ($collectionValueType = $internalType->getVariableTypes()[1] ?? null)) {
            return [];
        }

        return BackwardCompatibilityHelper::convertTypeToLegacyTypes($collectionValueType) ?? [];
    }

    private function validateCollectionArgument(array|self|null $collectionArgument, int $argumentIndex, string $argumentName): ?array
    {
        if (null === $collectionArgument) {
            return null;
        }

        if (\is_array($collectionArgument)) {
            foreach ($collectionArgument as $type) {
                if (!$type instanceof self) {
                    throw new \TypeError(sprintf('"%s()": Argument #%d (%s) must be of type "%s[]", "%s" or "null", array value "%s" given.', __METHOD__, $argumentIndex, $argumentName, self::class, self::class, get_debug_type($collectionArgument)));
                }
            }

            return $collectionArgument;
        }

        return [$collectionArgument];
    }
}
