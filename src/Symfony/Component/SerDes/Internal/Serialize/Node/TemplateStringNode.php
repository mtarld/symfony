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
final readonly class TemplateStringNode implements NodeInterface
{
    /**
     * @var list<string|VariableNode>
     */
    public array $parts;

    public function __construct(string|VariableNode ...$parts)
    {
        $this->parts = array_values($parts);
    }

    public function compile(Compiler $compiler): void
    {
        $templateString = '';
        foreach ($this->parts as $part) {
            if (\is_string($part)) {
                $templateString .= sprintf('%s', addcslashes($part, '"\\'));

                continue;
            }

            $templateString .= sprintf('{%s}', $compiler->subcompile($part));
        }

        $compiler->raw(sprintf('"%s"', $templateString));
    }

    public function optimize(Optimizer $optimizer): static
    {
        return new self(...array_map(fn (VariableNode|string $p): VariableNode|string => \is_string($p) ? $p : $optimizer->optimize($p), $this->parts));
    }
}
