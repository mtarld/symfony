<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Ast\Node;

use Symfony\Component\Marshaller\Native\Ast\Compiler;

/**
 * @internal
 */
final class IfNode implements NodeInterface
{
    /**
     * @param list<NodeInterface>                                              $onIf
     * @param list<NodeInterface>                                              $onElse
     * @param list<array{condition: NodeInterface, body: list<NodeInterface>}> $elseIfs
     */
    public function __construct(
        public readonly NodeInterface $condition,
        public readonly array $onIf,
        public readonly array $onElse = [],
        public readonly array $elseIfs = [],
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->line(sprintf('if (%s) {', $compiler->subcompile($this->condition)))
            ->indent();

        foreach ($this->onIf as $ifBodyNode) {
            $compiler->compile($ifBodyNode);
        }

        $compiler->outdent();

        foreach ($this->elseIfs as $elseIf) {
            $compiler
                ->line(sprintf('} elseif (%s) {', $compiler->subcompile($elseIf['condition'])))
                ->indent();

            foreach ($elseIf['body'] as $elseIfBodyNode) {
                $compiler->compile($elseIfBodyNode);
            }

            $compiler->outdent();
        }

        if (null !== $this->onElse) {
            $compiler
                ->line('} else {')
                ->indent();

            foreach ($this->onElse as $elseBodyNode) {
                $compiler->compile($elseBodyNode);
            }

            $compiler->outdent();
        }

        $compiler->line('}');
    }
}
