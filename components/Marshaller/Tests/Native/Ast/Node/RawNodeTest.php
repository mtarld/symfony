<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Ast\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Node\RawNode;

final class RawNodeTest extends TestCase
{
    public function testCompile(): void
    {
        (new RawNode('echo "raw php";'))->compile($compiler = new Compiler());
        $this->assertSame('echo "raw php";', $compiler->source());
    }
}
