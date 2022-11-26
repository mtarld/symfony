<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Ast;

use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;

/**
 * @internal
 */
final class Compiler
{
    private string $source = '';
    private int $indentationLevel = 0;

    // TODO really need to be fluent?
    public function reset(): static
    {
        $this->source = '';
        $this->indentationLevel = 0;

        return $this;
    }

    public function source(): string
    {
        return $this->source;
    }

    public function compile(NodeInterface $node): static
    {
        $node->compile($this);

        return $this;
    }

    public function subcompile(NodeInterface $node): string
    {
        $mainSource = $this->source;
        $this->source = '';

        $node->compile($this);
        $subCompiledSource = $this->source;

        $this->source = $mainSource;

        return $subCompiledSource;
    }

    public function raw(string $string): static
    {
        $this->source .= $string;

        return $this;
    }

    public function line(string $line): static
    {
        $this->source .= str_repeat(' ', 4 * $this->indentationLevel).$line.PHP_EOL;

        return $this;
    }

    public function repr(mixed $value, bool $escape = true): static
    {
        if (null === $value) {
            $this->raw('null');

            return $this;
        }

        if (\is_int($value) || \is_float($value)) {
            if (false !== $locale = setlocale(\LC_NUMERIC, 0)) {
                setlocale(\LC_NUMERIC, 'C');
            }

            $this->raw($value);

            if (false !== $locale) {
                setlocale(\LC_NUMERIC, $locale);
            }

            return $this;
        }

        if (\is_bool($value)) {
            $this->raw($value ? 'true' : 'false');

            return $this;
        }

        if (\is_string($value)) {
            if ($escape) {
                $value = addcslashes($value, "\0\t\"\$\\");
            }

            $this->raw(sprintf('"%s"', $value));

            return $this;
        }

        throw new \RuntimeException('TODO');
    }

    public function indent(): static
    {
        ++$this->indentationLevel;

        return $this;
    }

    public function outdent(): static
    {
        $this->indentationLevel = max(0, $this->indentationLevel - 1);

        return $this;
    }
}
