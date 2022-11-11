<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Option;

final class HooksOption
{
    /**
     * @var array<string, \Closure>
     */
    public readonly array $hooks;

    /**
     * @param array<string, callable> $hooks
     */
    public function __construct(array $hooks)
    {
        $closures = [];

        foreach ($hooks as $hookName => $hook) {
            if (!is_callable($hook)) {
                throw new \InvalidArgumentException(sprintf('Hook "%s" of attribute "%s" is an invalid callable.', $hookName, self::class));
            }

            $closures[$hookName] = \Closure::fromCallable($hook);
            $reflection = new \ReflectionFunction($closures[$hookName]);

            if (preg_match('/^property$|^(?:[\\w\\\\])+::\\$\\w+$/', $hookName)) {
                if (4 !== \count($reflection->getParameters())) {
                    throw new \InvalidArgumentException(sprintf('Hook "%s" of attribute "%s" must have exactly 4 arguments.', $hookName, self::class));
                }

                $propertyParameterType = $reflection->getParameters()[0]->getType();
                if (!$propertyParameterType instanceof \ReflectionNamedType || \ReflectionProperty::class !== $propertyParameterType->getName()) {
                    throw new \InvalidArgumentException(sprintf('Hook "%s" of attribute "%s" must have a "%s" for first argument.', $hookName, self::class, \ReflectionProperty::class));
                }

                $accessorParameterType = $reflection->getParameters()[1]->getType();
                if (!$accessorParameterType instanceof \ReflectionNamedType || 'string' !== $accessorParameterType->getName()) {
                    throw new \InvalidArgumentException(sprintf('Hook "%s" of attribute "%s" must have a "string" for second argument.', $hookName, self::class));
                }

                $formatParameterType = $reflection->getParameters()[2]->getType();
                if (!$formatParameterType instanceof \ReflectionNamedType || 'string' !== $formatParameterType->getName()) {
                    throw new \InvalidArgumentException(sprintf('Hook "%s" of attribute "%s" must have a "string" for third argument.', $hookName, self::class));
                }

                $contextParameterType = $reflection->getParameters()[3]->getType();
                if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
                    throw new \InvalidArgumentException(sprintf('Hook "%s" of attribute "%s" must have an "array" for fourth argument.', $hookName, self::class));
                }

                continue;
            }

            if (preg_match('/^function$|^(?:[\\w\\\\])+::\\w+\\(\\)$/', $hookName)) {
                if (4 !== \count($reflection->getParameters())) {
                    throw new \InvalidArgumentException(sprintf('Hook "%s" of attribute "%s" must have exactly 4 arguments.', $hookName, self::class));
                }

                $functionParameterType = $reflection->getParameters()[0]->getType();
                if (!$functionParameterType instanceof \ReflectionNamedType || \ReflectionFunction::class !== $functionParameterType->getName()) {
                    throw new \InvalidArgumentException(sprintf('Hook "%s" of attribute "%s" must have a "%s" for first argument.', $hookName, self::class, \ReflectionFunction::class));
                }

                $accessorParameterType = $reflection->getParameters()[1]->getType();
                if (!$accessorParameterType instanceof \ReflectionNamedType || 'string' !== $accessorParameterType->getName()) {
                    throw new \InvalidArgumentException(sprintf('Hook "%s" of attribute "%s" must have a "string" for second argument.', $hookName, self::class));
                }

                $formatParameterType = $reflection->getParameters()[2]->getType();
                if (!$formatParameterType instanceof \ReflectionNamedType || 'string' !== $formatParameterType->getName()) {
                    throw new \InvalidArgumentException(sprintf('Hook "%s" of attribute "%s" must have a "string" for third argument.', $hookName, self::class));
                }

                $contextParameterType = $reflection->getParameters()[3]->getType();
                if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
                    throw new \InvalidArgumentException(sprintf('Hook "%s" of attribute "%s" must have an "array" for fourth argument.', $hookName, self::class));
                }

                continue;
            }

            if (4 !== \count($reflection->getParameters())) {
                throw new \InvalidArgumentException(sprintf('Hook "%s" of attribute "%s" must have exactly 4 arguments.', $hookName, self::class));
            }

            $typeParameterType = $reflection->getParameters()[0]->getType();
            if (!$typeParameterType instanceof \ReflectionNamedType || \ReflectionFunction::class !== $typeParameterType->getName()) {
                throw new \InvalidArgumentException(sprintf('Hook "%s" of attribute "%s" must have a "%s" for first argument.', $hookName, self::class, \ReflectionFunction::class));
            }

            $accessorParameterType = $reflection->getParameters()[1]->getType();
            if (!$accessorParameterType instanceof \ReflectionNamedType || 'string' !== $accessorParameterType->getName()) {
                throw new \InvalidArgumentException(sprintf('Hook "%s" of attribute "%s" must have a "string" for second argument.', $hookName, self::class));
            }

            $formatParameterType = $reflection->getParameters()[2]->getType();
            if (!$formatParameterType instanceof \ReflectionNamedType || 'string' !== $formatParameterType->getName()) {
                throw new \InvalidArgumentException(sprintf('Hook "%s" of attribute "%s" must have a "string" for third argument.', $hookName, self::class));
            }

            $contextParameterType = $reflection->getParameters()[3]->getType();
            if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
                throw new \InvalidArgumentException(sprintf('Hook "%s" of attribute "%s" must have an "array" for fourth argument.', $hookName, self::class));
            }
        }

        $this->hooks = $closures;
    }
}
