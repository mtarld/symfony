<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Option;

use Symfony\Component\Marshaller\Context\OptionInterface;

final class HookOption implements OptionInterface
{
    public readonly \Closure $closure;

    /**
     * @param callable $callable
     */
    public function __construct(
        public readonly string $name,
        private readonly mixed $callable,
    ) {
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException(sprintf('Parameter "$callable" of attribute "%s" must be a valid callable.', self::class));
        }

        $this->closure = \Closure::fromCallable($callable);

        $reflection = new \ReflectionFunction($this->closure);

        if (($returnType = $reflection->getReturnType()) instanceof \ReflectionNamedType && ('void' === $returnType || 'never' === $returnType)) {
            throw new \InvalidArgumentException(sprintf('Callable of attribute "%s" must be not return "void" neither "never".', self::class));
        }

        $propertyArgumentType = ($reflection->getParameters()[0] ?? null)?->getType();
        if (!$propertyArgumentType instanceof \ReflectionNamedType || \ReflectionProperty::class !== $propertyArgumentType->getName()) {
            throw new \InvalidArgumentException(sprintf('First argument of attribute "%s"\'s callable must be a "%s".', self::class, \ReflectionProperty::class));
        }

        $accessorArgumentType = ($reflection->getParameters()[1] ?? null)?->getType();
        if (!$accessorArgumentType instanceof \ReflectionNamedType || 'string' !== $accessorArgumentType->getName()) {
            throw new \InvalidArgumentException(sprintf('Second argument of attribute "%s"\'s callable must be a "string".', self::class));
        }

        $contextArgumentType = ($reflection->getParameters()[2] ?? null)?->getType();
        if (!$contextArgumentType instanceof \ReflectionNamedType || 'array' !== $contextArgumentType->getName()) {
            throw new \InvalidArgumentException(sprintf('Third argument of attribute "%s"\'s callable must be an "array".', self::class));
        }
    }

    public function mergeNativeContext(array $nativeContext): array
    {
        if (!isset($nativeContext['hooks'])) {
            $nativeContext['hooks'] = [];
        }

        $nativeContext['hooks'][$this->name] = $this->closure;

        return $nativeContext;
    }
}
