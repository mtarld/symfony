<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Formatter
{
    public readonly \Closure $formatter;

    /**
     * @param string|array{0: string, 1: string} $callable
     */
    public function __construct(string|array $callable)
    {
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException(sprintf('Parameter "$callable" of attribute "%s" must be a valid callable.', self::class));
        }

        $this->formatter = \Closure::fromCallable($callable);
    }
}
