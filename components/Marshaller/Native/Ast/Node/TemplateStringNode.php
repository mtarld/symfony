<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Ast\Node;

use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Optimizer;

/**
 * @internal
 */
final class TemplateStringNode implements NodeInterface
{
    /**
     * @var list<string|VariableNode>
     */
    public readonly array $parts;

    public function __construct(string|VariableNode ...$parts)
    {
        $this->parts = array_values($parts);
    }

    public function compile(Compiler $compiler): void
    {
        $templateString = '';
        foreach ($this->parts as $part) {
            if (is_string($part)) {
                $templateString .= sprintf('%s', addcslashes($part, '"\\'));

                continue;
            }

            $templateString .= sprintf('{%s}', $compiler->subcompile($part));
        }

        $compiler->raw(sprintf('"%s"', $templateString));
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self(...array_map(fn (VariableNode|string $p): VariableNode|string => is_string($p) ? $p : $optimizer->optimize($p), $this->parts));
    }
}
