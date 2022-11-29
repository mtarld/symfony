<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Ast;

use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;

/**
 * @internal
 */
final class Compiler
{
    private readonly Optimizer $optimizer;
    private string $source = '';
    private int $indentationLevel = 0;

    public function __construct()
    {
        $this->optimizer = new Optimizer();
    }

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
        $this->optimizer->optimize($node)->compile($this);

        return $this;
    }

    public function subcompile(NodeInterface $node): string
    {
        $mainSource = $this->source;
        $this->source = '';

        $node->compile($this, $this->optimizer);
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
