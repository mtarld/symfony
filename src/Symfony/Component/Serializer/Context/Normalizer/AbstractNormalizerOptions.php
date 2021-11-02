<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Context\Normalizer;

/**
 * @internal
 */
abstract class AbstractNormalizerOptions
{
    /**
     * How many loops of circular reference to allow while normalizing.
     *
     * The value 1 means that when we encounter the same object a
     * second time, we consider that a circular reference.
     *
     * You can raise this value for special cases, e.g. in combination with the
     * max depth setting of the object normalizer.
     */
    protected ?int $circularReferenceLimit = null;

    /**
     * Instead of creating a new instance of an object, update the specified object.
     *
     * If you have a nested structure, child objects will be overwritten with
     * new instances unless you set DEEP_OBJECT_TO_POPULATE to true.
     */
    protected ?object $objectToPopulate = null;

    /**
     * Only (de)normalize attributes that are in the specified groups.
     *
     * @var list<string>|null
     */
    protected array|null $groups = null;

    /**
     * Limit (de)normalize to the specified names.
     *
     * For nested structures, this list needs to reflect the object tree.
     *
     * @var list<string>|null
     */
    protected ?array $attributes = null;

    /**
     * If $attributes is specified, and the source has fields that are not part of that list,
     * either ignore those attributes (true) or throw an ExtraAttributesException (false).
     */
    protected ?bool $allowExtraAttributes = null;

    /**
     * Hashmap of default values for constructor arguments.
     *
     * The names need to match the parameter names in the constructor arguments.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $defaultContructorArguments = null;

    /**
     * Hashmap of field name => callable to normalize this field.
     *
     * The callable is called if the field is encountered with the arguments:
     *
     * - mixed  $attributeValue value of this field
     * - object $object         the whole object being normalized
     * - string $attributeName  name of the attribute being normalized
     * - string $format         the requested format
     * - array  $context        the serialization context
     *
     * @var array<string, callable>|null
     */
    protected ?array $callbacks = null;

    /**
     * Handler to call when a circular reference has been detected.
     *
     * If you specify no handler, a CircularReferenceException is thrown.
     *
     * The method will be called with ($object, $format, $context) and its
     * return value is returned as the result of the normalize call.
     */
    protected ?callable $circularReferenceHandler = null;
    // TODO see how to handle both context in handlers... (maybe with reflection?)

    /**
     * Skip the specified attributes when normalizing an object tree.
     *
     * This list is applied to each element of nested structures.
     *
     * Note: The behaviour for nested structures is different from ATTRIBUTES
     * for historical reason. Aligning the behaviour would be a BC break.
     *
     * @var list<string>|null
     */
    protected ?array $ignoredAttributes = null;

    public function getCircularReferenceLimit(): int
    {
        return $this->circularReferenceLimit ?? 1;
    }

    public function setCircularReferenceLimit(int $circularReferenceLimit): static
    {
        $this->circularReferenceLimit = $circularReferenceLimit;

        return $this;
    }

    public function getObjectToPopulate(): ?object
    {
        return $this->objectToPopulate;
    }

    public function setObjectToPopulate(?object $objectToPopulate): static
    {
        $this->objectToPopulate = $objectToPopulate;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getGroups(): array
    {
        return $this->groups ?? [];
    }

    /**
     * @param list<string>|string $groups
     */
    public function setGroups(array|string $groups): static
    {
        $this->groups = is_scalar($groups) ? (array) $groups : $groups;

        return $this;
    }

    /**
     * @return list<string>|null
     */
    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    /**
     * @param list<string>|null $attributes
     */
    public function setAttributes(?array $attributes): static
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function isAllowExtraAttributes(): bool
    {
        return $this->allowExtraAttributes ?? true;
    }

    public function setAllowExtraAttributes(bool $allowExtraAttributes): static
    {
        $this->allowExtraAttributes = $allowExtraAttributes;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDefaultContructorArguments(): ?array
    {
        return $this->defaultContructorArguments;
    }

    /**
     * @param array<string, mixed>|null $defaultContructorArguments
     */
    public function setDefaultContructorArguments(?array $defaultContructorArguments): static
    {
        $this->defaultContructorArguments = $defaultContructorArguments;

        return $this;
    }

    /**
     * @return array<string, callable>|null
     */
    public function getCallbacks(): ?array
    {
        return $this->callbacks;
    }

    /**
     * @param array<string, callable>|null $callbacks
     */
    public function setCallbacks(?array $callbacks): static
    {
        // TODO validate
        $this->callbacks = $callbacks;

        return $this;
    }

    public function getCircularReferenceHandler(): ?callable
    {
        return $this->circularReferenceHandler;
    }

    public function setCircularReferenceHandler(callable $circularReferenceHandler): static
    {
        $this->circularReferenceHandler = $circularReferenceHandler;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getIgnoredAttributes(): array
    {
        return $this->ignoredAttributes ?? [];
    }

    /**
     * @param list<string> $ignoredAttributes
     */
    public function setIgnoredAttributes(array $ignoredAttributes): static
    {
        $this->ignoredAttributes = $ignoredAttributes;

        return $this;
    }

    public function merge(self $other): self
    {
        $this->circularReferenceLimit = $other->circularReferenceLimit;
        $this->objectToPopulate = $other->objectToPopulate;
        $this->groups = $other->groups;
        $this->attributes = $other->attributes;
        $this->allowExtraAttributes = $other->allowExtraAttributes;
        $this->defaultContructorArguments = $other->defaultContructorArguments;
        $this->callbacks = $other->callbacks;
        $this->circularReferenceHandler = $other->circularReferenceHandler;
        $this->ignoredAttributes = $other->ignoredAttributes;

        return $this;
    }

    /**
     * @internal
     *
     * @return array<string, mixed>
     */
    public function toLegacyContext(): array
    {
        return [
            'circular_reference_limit' => $this->getCircularReferenceLimit(),
            'object_to_populate' => $this->getObjectToPopulate(),
            'groups' => $this->getGroups(),
            'attributes' => $this->getAttributes(),
            'allow_extra_attributes' => $this->isAllowExtraAttributes(),
            'default_constructor_arguments' => $this->getDefaultContructorArguments(),
            'callbacks' => $this->getCallbacks(),
            'circular_reference_handler' => $this->getCircularReferenceHandler(),
            'ignored_attributes' => $this->getIgnoredAttributes(),
        ];
    }
}
