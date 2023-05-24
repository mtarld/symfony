<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Internal\Serialize\Node;

use Symfony\Component\SerDes\Internal\Serialize\Compiler;
use Symfony\Component\SerDes\Internal\Serialize\NodeInterface;
use Symfony\Component\SerDes\Internal\Serialize\Optimizer;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
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
        $valueName = $this->valueName;
        $byReference = false;

        if (str_starts_with($this->valueName, '&')) {
            $byReference = true;
            $valueName = substr($valueName, 1);
        }

        if (null === $this->keyName) {
            $compiler->line(sprintf(
                'foreach (%s as %s%s) {',
                $compiler->subcompile($this->collection),
                $byReference ? '&' : '',
                $compiler->subcompile(new VariableNode($valueName)),
            ));
        } else {
            $compiler->line(sprintf(
                'foreach (%s as %s => %s%s) {',
                $compiler->subcompile($this->collection),
                $compiler->subcompile(new VariableNode($this->keyName)),
                $byReference ? '&' : '',
                $compiler->subcompile(new VariableNode($valueName)),
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
