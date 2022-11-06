<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY)]
final class Formatter
{
    public \Closure $closure;

    /**
     * @param callable $callable
     */
    public function __construct(
        public readonly mixed $callable,
    ) {
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException(sprintf('Method of attribute "%s" must be a valid callable.', self::class));
        }

        $this->closure = \Closure::fromCallable($callable);

        $reflection = new \ReflectionFunction($this->closure);

        if (($returnType = $reflection->getReturnType()) instanceof \ReflectionNamedType && ('void' === $returnType || 'never' === $returnType)) {
            throw new \InvalidArgumentException(sprintf('Method of attribute "%s" must be not be "void" neither "never".', self::class));
        }

        if ([] === $reflection->getParameters()) {
            throw new \InvalidArgumentException(sprintf('Callable\'s signature of attribute "%s" must have one argument.', self::class));
        }
    }
}
