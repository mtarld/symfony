<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Template\Generator;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;

trait YieldTrait
{
    public function yield(string|Node $data, string|Node $key = null): Stmt
    {
        if (is_string($data)) {
            $data = new Scalar\String_($data);
        }

        if (is_string($key)) {
            $key = new Scalar\String_($key);
        }

        return new Stmt\Expression(new Expr\Yield_($data, $key));
    }
}
