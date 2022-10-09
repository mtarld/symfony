<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Metadata\Attribute;

use Symfony\Component\Marshaller\Attribute\Formatter;
use Symfony\Component\Marshaller\Context\Context;

final class FormatterAttribute
{
    public readonly string $class;
    public readonly string $method;

    public function __construct(\ReflectionAttribute $reflection)
    {
        if (Formatter::class !== $reflection->getName()) {
            throw new \RuntimeException('TODO');
        }

        try {
            $this->class = $reflection->getArguments()[0];
            new \ReflectionClass($this->class);
        } catch (\ReflectionException) {
            throw new \InvalidArgumentException(sprintf('The class "%s" defined in attribute "%s" does not exist.', $this->class, Formatter::class));
        }

        try {
            $this->method = $reflection->getArguments()[1];
            $reflection = new \ReflectionMethod($this->class, $this->method);
        } catch (\ReflectionException $e) {
            throw new \InvalidArgumentException(sprintf('The method "%s::%s()" defined in attribute "%s" does not exist.', $this->class, $this->method, Formatter::class));
        }

        if (!$reflection->isStatic()) {
            throw new \InvalidArgumentException(sprintf('Method of attribute "%s" must be static.', Formatter::class));
        }

        $returnType = $reflection->getReturnType()?->getName();
        if ('void' === $returnType || 'never' === $returnType) {
            throw new \InvalidArgumentException(sprintf('Method of attribute "%s" must be not be "void" neither "never".', Formatter::class));
        }

        $valueParameter = $reflection->getParameters()[0] ?? null;
        $contextParameter = $reflection->getParameters()[1] ?? null;
        if (null === $valueParameter || null === $contextParameter || Context::class !== $contextParameter->getType()->getName()) {
            throw new \InvalidArgumentException(sprintf('Parameters of method "%s::%s()" must be a "$value" and a "%s $context".', $this->class, $this->method, Context::class));
        }
    }
}
