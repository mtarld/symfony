<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Ast\Node;

use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Optimizer;

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

    public function compile(Compiler $compiler): void
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

        foreach ($this->body as $bodyNode) {
            $compiler->compile($bodyNode);
        }

        $compiler
            ->outdent()
            ->line('}');
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self($optimizer->optimize($this->collection), $this->keyName, $this->valueName, $optimizer->optimize($this->body));
    }
}
