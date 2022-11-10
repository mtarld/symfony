<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Template;

use Symfony\Polyfill\Marshaller\Metadata\HookExtractor;
use Symfony\Polyfill\Marshaller\Metadata\Type;

abstract class TemplateGenerator
{
    use PhpWriterTrait;

    private readonly HookExtractor $hookExtractor;

    public function __construct()
    {
        $this->hookExtractor = new HookExtractor();
    }

    /**
     * @param array<string, mixed> $context
     */
    abstract protected function generateScalar(Type $type, string $accessor, array $context): string;

    /**
     * @param array<string, mixed> $context
     */
    abstract protected function generateObject(Type $type, string $accessor, array $context): string;

    /**
     * @param array<string, mixed> $context
     */
    abstract protected function generateList(Type $type, string $accessor, array $context): string;

    /**
     * @param array<string, mixed> $context
     */
    abstract protected function generateDict(Type $type, string $accessor, array $context): string;

    /**
     * @param array<string, mixed> $context
     */
    abstract protected function generateNull(array $context): string;

    /**
     * @param array<string, mixed> $context
     */
    final public function generate(Type $type, string $accessor, array $context): string
    {
        $template = '';

        if ($type->isNullable()) {
            $template .= $this->writeLine("if (null === $accessor) {", $context);

            ++$context['indentation_level'];

            if (null !== $hook = $this->hookExtractor->extractFromType(new Type('null'), $context)) {
                $template .= $hook('null', $accessor, $context);
            } else {
                $template .= $this->null($context);
            }

            --$context['indentation_level'];
            $template .= $this->writeLine('} else {', $context);

            ++$context['indentation_level'];
        }

        if (null !== $hook = $this->hookExtractor->extractFromType($type, $context)) {
            $template .= $hook((string) $type, $accessor, $context);
        } else {
            $template .= match (true) {
                $type->isNull() => $this->null($context),
                $type->isScalar() => $this->scalar($type, $accessor, $context),
                $type->isObject() => $this->object($type, $accessor, $context),
                $type->isList() => $this->list($type, $accessor, $context),
                $type->isDict() => $this->dict($type, $accessor, $context),
                default => throw new \InvalidArgumentException(sprintf('Cannot handle "%s" type', $typeString)),
            };
        }

        if ($type->isNullable()) {
            --$context['indentation_level'];
            $template .= $this->writeLine('}', $context);
        }

        --$context['indentation_level'];

        return $template;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function scalar(Type $type, string $accessor, array $context): string
    {
        return $this->generateScalar($type, $accessor, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function object(Type $type, string $accessor, array $context): string
    {
        $className = $type->className();

        if (isset($context['classes'][$className])) {
            throw new \RuntimeException(sprintf('Circular reference on "%s" detected.', $className));
        }

        $context['classes'][$className] = true;

        return $this->generateObject($type, $accessor, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function list(Type $type, string $accessor, array $context): string
    {
        return $this->generateList($type, $accessor, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function dict(Type $type, string $accessor, array $context): string
    {
        return $this->generateDict($type, $accessor, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function null(array $context): string
    {
        return $this->generateNull($context);
    }
}
