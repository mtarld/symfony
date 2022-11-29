<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Ast\Node;

use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Optimizer;

/**
 * @internal
 */
final class ForEachNode implements NodeInterface
{
    /**
     * @param list<NodeInterface> $body
     */
    public function __construct(
        public readonly NodeInterface $collection,
        public readonly ?string $keyName,
        public readonly string $valueName,
        public readonly array $body,
    ) {
    }

    public function compile(Compiler $compiler, Optimizer $optimizer): void
    {
        if (null === $this->keyName) {
            $compiler->line(sprintf(
                'foreach (%s as %s) {',
                $compiler->subcompile($this->collection),
                $compiler->subcompile(new VariableNode($this->valueName)),
            ));
        } else {
            $compiler->line(sprintf(
                'foreach (%s as %s => %s) {',
                $compiler->subcompile($this->collection),
                $compiler->subcompile(new VariableNode($this->keyName)),
                $compiler->subcompile(new VariableNode($this->valueName)),
            ));
        }

        $compiler->indent();

        foreach ($optimizer->optimize($this->body) as $bodyNode) {
            $compiler->compile($bodyNode);
        }

        $compiler
            ->outdent()
            ->line('}');
    }
}
