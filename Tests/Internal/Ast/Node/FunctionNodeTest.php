<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Internal\Ast\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\VariableNode;

final class FunctionNodeTest extends TestCase
{
    public function testCompile(): void
    {
        (new FunctionNode('fooFunction', [new VariableNode('foo'), new ScalarNode(true)]))->compile($compiler = new Compiler());
        $this->assertSame('fooFunction($foo, true)', $compiler->source());
    }
}
