<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Ast\Node;

use Symfony\Component\Marshaller\Native\Ast\Compiler;

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
}
