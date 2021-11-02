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

abstract class AbstractObjectNormalizerOptions
{
    /**
     * Whether to respect the max depth metadata on fields.
     */
    protected ?bool $enableMaxDepth = null;

    /**
     * Pattern to track the current depth in the context.
     */
    protected ?string $depthKeyPattern = null;

    /**
     * Whether to verify that types match while denormalizing.
     */
    protected ?bool $disableTypeEnforcement = null;

    /**
     * Whether fields with the value `null` should be skipped from output.
     */
    protected ?bool $skipNullValues = null;

    /**
     * Whether uninitialized typed class properties should be excluded when normalizing.
     */
    protected ?bool $skipUnitializedValues = null;

    /**
     * Callback to allow to set a value for an attribute when the max depth has
     * been reached.
     *
     * If no callback is given, the attribute is skipped. If a callable is
     * given, its return value is used (even if null).
     *
     * The arguments are:
     *
     * - mixed  $attributeValue value of this field
     * - object $object         the whole object being normalized
     * - string $attributeName  name of the attribute being normalized
     * - string $format         the requested format
     * - array  $context        the serialization context
     */
    protected ?callable $maxDepthHandler = null;

    /**
     * Context key that are not relevant to determine which attributes
     * of an object to (de)normalize.
     *
     * @var list<string>
     */
    protected ?array $excludeFromCacheKey = null;

    /**
     * Whether to also populate existing objects on attributes of the main object.
     *
     * Setting this to true is only useful if you also specify the root object
     * in $objectToPopulate
     */
    protected ?bool $deepObjectToPopulate = null;

    /**
     * Whether an empty object should be kept as an object (in
     * JSON: {}) or converted to a list (in JSON: []).
     */
    protected ?bool $preserveEmptyObjects = null;
}
