<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Formatter
{
    public readonly \Closure $formatter;

    /**
     * @param callable $callable
     */
    public function __construct(string|array $callable)
    {
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException(sprintf('Parameter "$callable" of attribute "%s" must be a valid callable.', self::class));
        }

        $this->formatter = \Closure::fromCallable($callable);

        $reflection = new \ReflectionFunction($this->formatter);

        if (($returnType = $reflection->getReturnType()) instanceof \ReflectionNamedType && ('void' === $returnType->getName() || 'never' === $returnType->getName())) {
            throw new \InvalidArgumentException(sprintf('Callable of attribute "%s" must be not return "void" nor "never".', self::class));
        }

        if (null !== $reflection->getClosureScopeClass() && !$reflection->isStatic()) {
            throw new \InvalidArgumentException(sprintf('Callable of attribute "%s" must be static.', self::class));
        }

        if (null !== ($contextParameter = $reflection->getParameters()[1] ?? null)) {
            $contextParameterType = $contextParameter->getType();

            if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
                throw new \InvalidArgumentException(sprintf('Callable of attribute "%s" second argument must be an array.', self::class));
            }
        }
    }
}
