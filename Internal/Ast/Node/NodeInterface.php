<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Ast\Node;

use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Optimizer;

/**
 * @internal
 */
interface NodeInterface
{
    public function compile(Compiler $compiler): void;

    public function optimize(Optimizer $optimizer): static;
}
