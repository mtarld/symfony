<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Ast\Node;

use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Optimizer;

/**
 * @internal
 */
final class ClosureNode implements NodeInterface
{
    /**
     * @param list<NodeInterface> $body
     */
    public function __construct(
        public readonly ArgumentsNode $arguments,
        public readonly ?string $returnType,
        public readonly bool $static,
        public readonly array $body,
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $staticSource = $this->static ? 'static ' : '';
        $argumentsSource = $compiler->subcompile($this->arguments);
        $returnTypeSource = $this->returnType ? ': '.$this->returnType : '';

        $compiler
            ->raw(sprintf('%sfunction (%s)%s {', $staticSource, $argumentsSource, $returnTypeSource).PHP_EOL)
            ->indent();

        foreach ($this->body as $bodyNode) {
            $compiler->compile($bodyNode);
        }

        $compiler
            ->outdent()
            ->raw('}');
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self(
            $optimizer->optimize($this->arguments),
            $this->returnType,
            $this->static,
            $optimizer->optimize($this->body),
        );
    }
}
