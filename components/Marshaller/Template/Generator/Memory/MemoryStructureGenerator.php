<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Template\Generator\Memory;

use Symfony\Component\Marshaller\Template\Generator\StructureGeneratorInterface;

final class MemoryStructureGenerator implements StructureGeneratorInterface
{
    public function generateBeginning(): array
    {
        return [];
    }

    public function generateEnding(): array
    {
        return [];
    }
}
