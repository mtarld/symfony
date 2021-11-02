<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Normalizer;

use Symfony\Component\Serializer\Context\Context;
use Symfony\Component\Serializer\Context\Normalizer\AbstractNormalizerOptions;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

/**
 * Normalizer implementation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
abstract class AbstractNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface, CacheableSupportsMethodInterface
{
    use ObjectToPopulateTrait;
    use SerializerAwareTrait;

    /* constants to configure the context */

    /**
     * How many loops of circular reference to allow while normalizing.
     *
     * The default value of 1 means that when we encounter the same object a
     * second time, we consider that a circular reference.
     *
     * You can raise this value for special cases, e.g. in combination with the
     * max depth setting of the object normalizer.
     *
     * @deprecated since symfony/serializer 6.1, use Context instead
     */
    public const CIRCULAR_REFERENCE_LIMIT = 'circular_reference_limit';

    /**
     * Instead of creating a new instance of an object, update the specified object.
     *
     * If you have a nested structure, child objects will be overwritten with
     * new instances unless you set DEEP_OBJECT_TO_POPULATE to true.
     *
     * @deprecated since symfony/serializer 6.1, use Context instead
     */
    public const OBJECT_TO_POPULATE = 'object_to_populate';

    /**
     * Only (de)normalize attributes that are in the specified groups.
     *
     * @deprecated since symfony/serializer 6.1, use Context instead
     */
    public const GROUPS = 'groups';

    /**
     * Limit (de)normalize to the specified names.
     *
     * For nested structures, this list needs to reflect the object tree.
     *
     * @deprecated since symfony/serializer 6.1, use Context instead
     */
    public const ATTRIBUTES = 'attributes';

    /**
     * If ATTRIBUTES are specified, and the source has fields that are not part of that list,
     * either ignore those attributes (true) or throw an ExtraAttributesException (false).
     *
     * @deprecated since symfony/serializer 6.1, use Context instead
     */
    public const ALLOW_EXTRA_ATTRIBUTES = 'allow_extra_attributes';

    /**
     * Hashmap of default values for constructor arguments.
     *
     * The names need to match the parameter names in the constructor arguments.
     *
     * @deprecated since symfony/serializer 6.1, use Context instead
     */
    public const DEFAULT_CONSTRUCTOR_ARGUMENTS = 'default_constructor_arguments';

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
     * @deprecated since symfony/serializer 6.1, use Context instead
     */
    public const CALLBACKS = 'callbacks';

    /**
     * Handler to call when a circular reference has been detected.
     *
     * If you specify no handler, a CircularReferenceException is thrown.
     *
     * The method will be called with ($object, $format, $context) and its
     * return value is returned as the result of the normalize call.
     *
     * @deprecated since symfony/serializer 6.1, use Context instead
     */
    public const CIRCULAR_REFERENCE_HANDLER = 'circular_reference_handler';

    /**
     * Skip the specified attributes when normalizing an object tree.
     *
     * This list is applied to each element of nested structures.
     *
     * Note: The behaviour for nested structures is different from ATTRIBUTES
     * for historical reason. Aligning the behaviour would be a BC break.
     *
     * @deprecated since symfony/serializer 6.1, use Context instead
     */
    public const IGNORED_ATTRIBUTES = 'ignored_attributes';

    /**
     * @internal
     */
    protected const CIRCULAR_REFERENCE_LIMIT_COUNTERS = 'circular_reference_limit_counters';

    /**
     * @deprecated since symfony/serializer 6.1, use defaultOptions instead
     */
    protected $defaultContext = [
        'allow_extra_attributes' => true,
        'circular_reference_handler' => null,
        'circular_reference_limit' => 1,
        'ignored_attributes' => [],
    ];

    /**
     * @var ClassMetadataFactoryInterface|null
     */
    protected $classMetadataFactory;

    /**
     * @var NameConverterInterface|null
     */
    protected $nameConverter;

    protected ?AbstractNormalizerOptions $defaultOptions = null;

    private array $circularReferenceCounters = [];

    /**
     * Sets the {@link ClassMetadataFactoryInterface} to use.
     *
     * @param Context|null $defaultContext
     */
    public function __construct(ClassMetadataFactoryInterface $classMetadataFactory = null, NameConverterInterface $nameConverter = null /*, Context $defaultContext = null */)
    {
        /** @var Context|array|null $defaultContext */
        $defaultContext = 2 < \func_num_args() ? \func_get_arg(2) : null;

        $this->classMetadataFactory = $classMetadataFactory;
        $this->nameConverter = $nameConverter;

        if (\is_array($defaultContext)) {
            trigger_deprecation('symfony/serializer', '6.1', 'Passing an array for $defaultContext is deprecated.');

            $normalizerOptions = new class extends AbstractNormalizerOptions {};
            $this->defaultContext = array_merge($normalizerOptions->toLegacyContext(), $defaultContext);

            if (isset($this->defaultContext['callbacks'])) {
                if (!\is_array($this->defaultContext['callbacks'])) {
                    throw new InvalidArgumentException('The "callbacks" default context option must be an array of callables.');
                }

                foreach ($this->defaultContext['callback'] as $attribute => $callback) {
                    if (!\is_callable($callback)) {
                        throw new InvalidArgumentException(sprintf('Invalid callback found for attribute "%s" in the "callbacks" default context option.', $attribute));
                    }
                }
            }

            if (isset($this->defaultContext['circular_reference_handler']) && !\is_callable($this->defaultContext['circular_reference_handler'])) {
                throw new InvalidArgumentException('Invalid callback found in the "circular_reference_handler" default context option.');
            }

            return;
        }


        $this->defaultOptions = $defaultContext?->getOptions(static::getOptionsType()) ?? new class extends AbstractNormalizerOptions {};
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return false;
    }

    /**
     * Detects if the configured circular reference limit is reached.
     *
     * @param Context $context
     *
     * @throws CircularReferenceException
     */
    protected function isCircularReference(object $object, /* Context $context */): bool
    {
        $context = $this->getContext(1 < \func_num_args() ? \func_get_arg(1) : null);
        $objectHash = spl_object_hash($object);

        if (isset($this->circularReferenceCounters[$objectHash])) {
            if ($this->circularReferenceCounters[$objectHash] >= $context['circular_reference_limit']) {
                unset($this->circularReferenceCounters[$objectHash]);

                return true;
            }

            ++$this->circularReferenceCounters[$objectHash];
        } else {
            ++$this->circularReferenceCounters[$objectHash];
        }

        return false;
    }

    /**
     * Handles a circular reference.
     *
     * If a circular reference handler is set, it will be called. Otherwise, a
     * {@class CircularReferenceException} will be thrown.
     *
     * @final
     *
     * @param Context|null $context
     *
     * @throws CircularReferenceException
     */
    protected function handleCircularReference(object $object, string $format = null /*, Context $context = null */): mixed
    {
        /** @var Context|array|null $context */
        $context = 2 < \func_num_args() ? \func_get_arg(2) : null; // TODO check every numbers

        $legacyContext = $this->getContext($context);

        $circularReferenceHandler = $legacyContext['circular_reference_handler'];

        if ($circularReferenceHandler) {
            $contextReflectionParameter = (new \ReflectionMethod($circularReferenceHandler))->getParameters()[2] ?? null;
            $contextReflectionParameterType = $contextReflectionParameter?->getType()?->getName();
            // TODO verify it
            if (null !== $contextReflectionParameterType && Context::class !== $contextReflectionParameterType) {
                trigger_deprecation('symfony/serializer', '6.1', 'Handling an array as the third argument of circule reference handler is deprecated.'); // TODO better deprecations

                return $circularReferenceHandler($object, $format, $legacyContext);
            }

            return $circularReferenceHandler($object, $format, $context);
        }

        throw new CircularReferenceException(sprintf('A circular reference has been detected when serializing the object of class "%s" (configured limit: %d).', get_debug_type($object), $legacyContext['circular_reference_limit']));
    }

    /**
     * Gets attributes to normalize using groups.
     *
     * @param bool $attributesAsString If false, return an array of {@link AttributeMetadataInterface}
     *
     * @throws LogicException if the 'allow_extra_attributes' context variable is false and no class metadata factory is provided
     *
     * @return string[]|AttributeMetadataInterface[]|bool
     */
    protected function getAllowedAttributes(string|object $classOrObject, array $context, bool $attributesAsString = false): array|bool // TODO stuck in here....
    {
        $allowExtraAttributes = $context[self::ALLOW_EXTRA_ATTRIBUTES] ?? $this->defaultContext[self::ALLOW_EXTRA_ATTRIBUTES];
        if (!$this->classMetadataFactory) {
            if (!$allowExtraAttributes) {
                throw new LogicException(sprintf('A class metadata factory must be provided in the constructor when setting "%s" to false.', self::ALLOW_EXTRA_ATTRIBUTES));
            }

            return false;
        }

        $groups = $this->getGroups($context);

        $allowedAttributes = [];
        $ignoreUsed = false;
        foreach ($this->classMetadataFactory->getMetadataFor($classOrObject)->getAttributesMetadata() as $attributeMetadata) {
            if ($ignore = $attributeMetadata->isIgnored()) {
                $ignoreUsed = true;
            }

            // If you update this check, update accordingly the one in Symfony\Component\PropertyInfo\Extractor\SerializerExtractor::getProperties()
            if (
                !$ignore &&
                ([] === $groups || array_intersect(array_merge($attributeMetadata->getGroups(), ['*']), $groups)) &&
                $this->isAllowedAttribute($classOrObject, $name = $attributeMetadata->getName(), null, $context)
            ) {
                $allowedAttributes[] = $attributesAsString ? $name : $attributeMetadata;
            }
        }

        if (!$ignoreUsed && [] === $groups && $allowExtraAttributes) {
            // Backward Compatibility with the code using this method written before the introduction of @Ignore
            return false;
        }

        return $allowedAttributes;
    }

    /**
     * @param Context $context
     */
    protected function getGroups(/* Context $context */): array
    {
        $context = $this->getContext(0 < \func_num_args() ? \func_get_arg(0) : null);

        $groups = $context['groups'];

        return is_scalar($groups) ? (array) $groups : $groups;
    }

    /**
     * Is this attribute allowed?
     *
     * @param Context|null $context
     */
    protected function isAllowedAttribute(object|string $classOrObject, string $attribute, string $format = null, /* Context $context = null */): bool
    {
        $context = $this->getContext(3 < \func_num_args() ? \func_get_arg(3) : null);

        if (\in_array($attribute, $context['ignored_attributes'])) {
            return false;
        }

        $attributes = $context['attributes'];
        if (isset($attributes[$attribute])) {
            // Nested attributes
            return true;
        }

        if (\is_array($attributes)) {
            return \in_array($attribute, $attributes, true);
        }

        return true;
    }

    /**
     * Normalizes the given data to an array. It's particularly useful during
     * the denormalization process.
     */
    protected function prepareForDenormalization(object|array|null $data): array
    {
        return (array) $data;
    }

    /**
     * Returns the method to use to construct an object. This method must be either
     * the object constructor or static.
     */
    protected function getConstructor(array &$data, string $class, array &$context, \ReflectionClass $reflectionClass, array|bool $allowedAttributes): ?\ReflectionMethod // TODO stuck
    {
        return $reflectionClass->getConstructor();
    }

    /**
     * Instantiates an object using constructor parameters when needed.
     *
     * This method also allows to denormalize data into an existing object if
     * it is present in the context with the object_to_populate. This object
     * is removed from the context before being returned to avoid side effects
     * when recursively normalizing an object graph.
     *
     * @throws RuntimeException
     * @throws MissingConstructorArgumentsException
     */
    protected function instantiateObject(array &$data, string $class, array &$context, \ReflectionClass $reflectionClass, array|bool $allowedAttributes, string $format = null): object // TODO stuck
    {
        if (null !== $object = $this->extractObjectToPopulate($class, $context, self::OBJECT_TO_POPULATE)) {
            unset($context[self::OBJECT_TO_POPULATE]);

            return $object;
        }
        // clean up even if no match
        unset($context[static::OBJECT_TO_POPULATE]);

        $constructor = $this->getConstructor($data, $class, $context, $reflectionClass, $allowedAttributes);
        if ($constructor) {
            if (true !== $constructor->isPublic()) {
                return $reflectionClass->newInstanceWithoutConstructor();
            }

            $constructorParameters = $constructor->getParameters();

            $params = [];
            foreach ($constructorParameters as $constructorParameter) {
                $paramName = $constructorParameter->name;
                $key = $this->nameConverter ? $this->nameConverter->normalize($paramName, $class, $format, $context) : $paramName;

                $allowed = false === $allowedAttributes || \in_array($paramName, $allowedAttributes);
                $ignored = !$this->isAllowedAttribute($class, $paramName, $format, $context);
                if ($constructorParameter->isVariadic()) {
                    if ($allowed && !$ignored && (isset($data[$key]) || \array_key_exists($key, $data))) {
                        if (!\is_array($data[$paramName])) {
                            throw new RuntimeException(sprintf('Cannot create an instance of "%s" from serialized data because the variadic parameter "%s" can only accept an array.', $class, $constructorParameter->name));
                        }

                        $variadicParameters = [];
                        foreach ($data[$paramName] as $parameterData) {
                            $variadicParameters[] = $this->denormalizeParameter($reflectionClass, $constructorParameter, $paramName, $parameterData, $context, $format);
                        }

                        $params = array_merge($params, $variadicParameters);
                        unset($data[$key]);
                    }
                } elseif ($allowed && !$ignored && (isset($data[$key]) || \array_key_exists($key, $data))) {
                    $parameterData = $data[$key];
                    if (null === $parameterData && $constructorParameter->allowsNull()) {
                        $params[] = null;
                        // Don't run set for a parameter passed to the constructor
                        unset($data[$key]);
                        continue;
                    }

                    // Don't run set for a parameter passed to the constructor
                    try {
                        $params[] = $this->denormalizeParameter($reflectionClass, $constructorParameter, $paramName, $parameterData, $context, $format);
                    } catch (NotNormalizableValueException $exception) {
                        if (!isset($context['not_normalizable_value_exceptions'])) {
                            throw $exception;
                        }

                        $context['not_normalizable_value_exceptions'][] = $exception;
                        $params[] = $parameterData;
                    }
                    unset($data[$key]);
                } elseif (\array_key_exists($key, $context[static::DEFAULT_CONSTRUCTOR_ARGUMENTS][$class] ?? [])) {
                    $params[] = $context[static::DEFAULT_CONSTRUCTOR_ARGUMENTS][$class][$key];
                } elseif (\array_key_exists($key, $this->defaultContext[self::DEFAULT_CONSTRUCTOR_ARGUMENTS][$class] ?? [])) {
                    $params[] = $this->defaultContext[self::DEFAULT_CONSTRUCTOR_ARGUMENTS][$class][$key];
                } elseif ($constructorParameter->isDefaultValueAvailable()) {
                    $params[] = $constructorParameter->getDefaultValue();
                } elseif ($constructorParameter->hasType() && $constructorParameter->getType()->allowsNull()) {
                    $params[] = null;
                } else {
                    if (!isset($context['not_normalizable_value_exceptions'])) {
                        throw new MissingConstructorArgumentsException(sprintf('Cannot create an instance of "%s" from serialized data because its constructor requires parameter "%s" to be present.', $class, $constructorParameter->name), 0, null, [$constructorParameter->name]);
                    }

                    $exception = NotNormalizableValueException::createForUnexpectedDataType(
                        sprintf('Failed to create object because the object miss the "%s" property.', $constructorParameter->name),
                        $data,
                        ['unknown'],
                        $context['deserialization_path'] ?? null,
                        true
                    );
                    $context['not_normalizable_value_exceptions'][] = $exception;

                    return $reflectionClass->newInstanceWithoutConstructor();
                }
            }

            if ($constructor->isConstructor()) {
                return $reflectionClass->newInstanceArgs($params);
            } else {
                return $constructor->invokeArgs(null, $params);
            }
        }

        return new $class();
    }

    /**
     * @internal
     */
    protected function denormalizeParameter(\ReflectionClass $class, \ReflectionParameter $parameter, string $parameterName, mixed $parameterData, array $context, string $format = null): mixed
    {
        try {
            if (($parameterType = $parameter->getType()) instanceof \ReflectionNamedType && !$parameterType->isBuiltin()) {
                $parameterClass = $parameterType->getName();
                new \ReflectionClass($parameterClass); // throws a \ReflectionException if the class doesn't exist

                if (!$this->serializer instanceof DenormalizerInterface) {
                    throw new LogicException(sprintf('Cannot create an instance of "%s" from serialized data because the serializer inject in "%s" is not a denormalizer.', $parameterClass, static::class));
                }

                return $this->serializer->denormalize($parameterData, $parameterClass, $format, $this->createChildContext($context, $parameterName, $format));
            }
        } catch (\ReflectionException $e) {
            throw new RuntimeException(sprintf('Could not determine the class of the parameter "%s".', $parameterName), 0, $e);
        } catch (MissingConstructorArgumentsException $e) {
            if (!$parameter->getType()->allowsNull()) {
                throw $e;
            }

            return null;
        }

        return $parameterData;
    }

    /**
     * @internal
     */
    protected function createChildContext(array $parentContext, string $attribute, ?string $format): array
    {
        if (isset($parentContext['attributes'][$attribute])) {
            $parentContext['attributes'] = $parentContext['attributes'][$attribute];
        } else {
            unset($parentContext['attributes']);
        }

        return $parentContext;
    }

    protected function getOptions(?Context $context): AbstractNormalizerOptions
    {
        $options = $context?->getOptions(static::getOptionsType());

        return null !== $options ? $options->merge($this->defaultOptions) : $this->defaultOptions;
    }

    /**
     * @return class-string<AbstractNormalizerOptions>
     */
    /* abstract */ protected static function getOptionsType(): string
    {
        // TODO
        trigger_deprecation('symfony/serializer', '6.1', 'You must override getOptionsType which will become abstract');

        return AbstractNormalizerOptions::class;
    }

    /**
     * Prepare a context array filled with defaults based
     * on either a Context object or the legacy context array.
     *
     * Used for BC layer.
     *
     * @internal
     *
     * @param Context|array<string, mixed>|null $context
     *
     * @return array<string, mixed>
     */
    protected function getContext(Context|array|null $context): array
    {
        $defaultLegacyContext = null !== $this->defaultOptions ? $this->defaultOptions->toLegacyContext() : $this->defaultContext;

        if (null === $context) {
            return $defaultLegacyContext;
        }

        if (\is_array($context)) {
            trigger_deprecation('symfony/serializer', '6.1', 'Passing an array for $context is deprecated.');

            return [
                'circular_reference_limit' => $context['circular_reference_limit'] ?? $defaultLegacyContext['circular_reference_limit'],
                'object_to_populate' => $context['object_to_populate'] ?? $defaultLegacyContext['object_to_populate'],
                'groups' => $context['groups'] ?? $defaultLegacyContext['groups'],
                'attributes' => $context['attributes'] ?? $defaultLegacyContext['attributes'],
                'allow_extra_attributes' => $context['allow_extra_attributes'] ?? $defaultLegacyContext['allow_extra_attributes'],
                'default_constructor_arguments' => $context['default_constructor_arguments'] ?? $defaultLegacyContext['default_constructor_arguments'],
                'callbacks' => $context['callbacks'] ?? $defaultLegacyContext['callbacks'],
                'circular_reference_handler' => $context['circular_reference_handler'] ?? $defaultLegacyContext['circular_reference_handler'],
                'ignored_attributes' => $context['ignored_attributes'] ?? $defaultLegacyContext['ignored_attributes'],
            ];
        }

        return $this->getOptions($context)->toLegacyContext();
    }
}
