<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\TemplateGenerator\Node;

use Symfony\Component\Serializer\Serialize\TemplateGenerator\Compiler;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\NodeInterface;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Optimizer;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final readonly class ClosureNode implements NodeInterface
{
    /**
     * @param list<NodeInterface> $body
     * @param list<VariableNode>  $uses
     */
    public function __construct(
        public ArgumentsNode $arguments,
        public ?string $returnType,
        public bool $static,
        public array $body,
        public array $uses = [],
    ) {
    }

    public function compile(Compiler $compiler): void
    {
        $staticSource = $this->static ? 'static ' : '';
        $argumentsSource = $compiler->subcompile($this->arguments);
        $usesSource = [] !== $this->uses ? sprintf(' use (%s)', implode(', ', array_map(fn (NodeInterface $v): string => $compiler->subcompile($v), $this->uses))) : '';
        $returnTypeSource = $this->returnType ? ': '.$this->returnType : '';

        $compiler
            ->raw(sprintf('%sfunction (%s)%s%s {', $staticSource, $argumentsSource, $usesSource, $returnTypeSource).\PHP_EOL)
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
            $optimizer->optimize($this->uses),
        );
    }
}
