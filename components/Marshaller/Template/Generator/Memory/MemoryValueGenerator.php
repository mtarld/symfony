<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Template\Generator\Memory;

use PhpParser\Node\Expr;
use Symfony\Component\Marshaller\Metadata\ValueMetadata;
use Symfony\Component\Marshaller\Template\Generator\ValueGeneratorInterface;

final class MemoryValueGenerator implements ValueGeneratorInterface
{
    public function generate(ValueMetadata $value, Expr $accessor): array
    {
        dd('ok');
        return [$accessor];
    }

    public function canGenerate(ValueMetadata $value): bool
    {
        return $value->isScalar();
    }
}
