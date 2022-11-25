<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

use phpDocumentor\Reflection\Types\ContextFactory;

/**
 * @internal
 */
final class TypeNameResolver
{
    /**
     * @param class-string                $className
     * @param array<string, class-string> $uses
     * @param list<string>                $templateNames
     */
    public function __construct(
        private readonly string $className,
        private readonly string $namespace,
        private readonly array $uses,
        private readonly array $templateNames,
    ) {
    }

    /**
     * @param class-string $fqcn
     * @param list<string> $templateNames
     */
    public static function createForClass(string $className, array $templateNames): self
    {
        $reflection = new \ReflectionClass($className);
        $context = (new ContextFactory())->createFromReflector($reflection);

        $namespace = $context->getNamespace();
        $uses = $context->getNamespaceAliases();
        $className = str_replace($namespace.'\\', '', $reflection->getName());

        return new self($className, $namespace, $uses, $templateNames);
    }

    public function resolveRootClass(): string
    {
        return $this->resolve($this->className);
    }

    public function resolveParentClass(): string
    {
        $rootClass = $this->resolveRootClass();

        if (false === $parentReflection = (new \ReflectionClass($rootClass))->getParentClass()) {
            throw new \LogicException(sprintf('"%s" class do not extend any class.', $rootClass));
        }

        $parentClassName = str_replace($this->namespace.'\\', '', $parentReflection->getName());

        return $this->resolve($parentClassName);
    }

    public function resolve(string $name): string
    {
        if (in_array($name, $this->templateNames)) {
            return $name;
        }

        if (str_starts_with($name, '\\')) {
            return ltrim($name, '\\');
        }

        $nameParts = explode('\\', $name);
        $usedPart = $nameParts[0];

        if (!isset($this->uses[$usedPart])) {
            return null !== $this->namespace ? sprintf('%s\\%s', $this->namespace, $name) : $name;
        }

        if (1 === \count($nameParts)) {
            return $this->uses[$usedPart];
        }

        array_shift($nameParts);

        return sprintf('%s\\%s', $this->uses[$usedPart], implode('\\', $nameParts));
    }
}
