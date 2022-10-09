<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Template\Generator;

use PhpParser\Node\Stmt;

interface StructureGeneratorInterface
{
    /**
     * @return array<Stmt>
     */
    public function generateBeginning(): array;

    /**
     * @return array<Stmt>
     */
    public function generateEnding(): array;
}
