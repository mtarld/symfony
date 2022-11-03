<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Template\Generator\Json;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use Symfony\Component\Marshaller\Metadata\ValueMetadata;
use Symfony\Component\Marshaller\Template\Generator\OutputWriterTrait;
use Symfony\Component\Marshaller\Template\Generator\ValueGeneratorInterface;

final class JsonScalarValueGenerator implements ValueGeneratorInterface
{
    use OutputWriterTrait;

    public function generate(ValueMetadata $value, Expr $accessor): array
    {
        return [
            $this->write(new Expr\FuncCall(new Name('json_encode'), [$accessor])),
        ];
    }

    public function canGenerate(ValueMetadata $value): bool
    {
        return $value->isScalar();
    }
}
