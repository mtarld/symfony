<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Template\Generator;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use Symfony\Component\Marshaller\Metadata\ValueMetadata;

interface ValueGeneratorInterface
{
    /**
     * @return array<Stmt>
     */
    public function generate(ValueMetadata $value, Expr $accessor): array;

    public function canGenerate(ValueMetadata $value): bool;
}
