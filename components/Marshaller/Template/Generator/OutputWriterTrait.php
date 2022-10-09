<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Template\Generator;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

trait OutputWriterTrait
{
    public function write(string|Node $data): Stmt
    {
        if (is_string($data)) {
            $data = new Scalar\String_($data);
        }

        return new Stmt\Expression(new Expr\MethodCall(new Expr\Variable('output'), 'write', [$data]));
    }
}
