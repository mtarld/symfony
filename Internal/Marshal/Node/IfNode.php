<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Marshal\Node;

use Symfony\Component\Marshaller\Internal\Marshal\Compiler;
use Symfony\Component\Marshaller\Internal\Marshal\NodeInterface;
use Symfony\Component\Marshaller\Internal\Marshal\Optimizer;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
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

        if ([] !== $this->onElse) {
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

    public function optimize(Optimizer $optimizer): static
    {
        return new self(
            $optimizer->optimize($this->condition),
            $optimizer->optimize($this->onIf),
            $optimizer->optimize($this->onElse),
            array_map(fn (array $e): array => [
                'condition' => $optimizer->optimize($e['condition']),
                'body' => $optimizer->optimize($e['body']),
            ], $this->elseIfs),
        );
    }
}
