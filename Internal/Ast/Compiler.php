<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Ast;

use Symfony\Component\Marshaller\Internal\Ast\Node\NodeInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
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
        $this->source .= str_repeat(' ', 4 * $this->indentationLevel).$line.\PHP_EOL;

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
