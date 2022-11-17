<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Formatter
{
    public readonly string $callable;

    /**
     * @param string|array{0: class-string, 1: string} $callable
     */
    public function __construct(string|array $callable)
    {
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException(sprintf('Parameter "$callable" of attribute "%s" must be a valid callable.', self::class));
        }

        $reflection = new \ReflectionFunction(\Closure::fromCallable($callable));

        if (($returnType = $reflection->getReturnType()) instanceof \ReflectionNamedType && ('void' === $returnType || 'never' === $returnType)) {
            throw new \InvalidArgumentException(sprintf('Callable of attribute "%s" must be not return "void" nor "never".', self::class));
        }

        if (null !== $reflection->getClosureScopeClass() && !$reflection->isStatic()) {
            throw new \InvalidArgumentException(sprintf('Callable of attribute "%s" must be static.', self::class));
        }

        if (2 !== \count($reflection->getParameters())) {
            throw new \InvalidArgumentException(sprintf('Callable of attribute "%s" must have exactly two arguments.', self::class));
        }

        $contextParameterType = $reflection->getParameters()[1]->getType();
        if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
            throw new \InvalidArgumentException(sprintf('Callable of attribute "%s" must have an array for second argument.', self::class));
        }

        if (is_array($callable)) {
            $callable = sprintf('%s::%s', $callable[0], $callable[1]);
        }

        $this->callable = $callable;
    }
}
