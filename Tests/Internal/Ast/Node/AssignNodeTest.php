<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Internal\Ast\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Node\AssignNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\VariableNode;

final class AssignNodeTest extends TestCase
{
    public function testCompile(): void
    {
        (new AssignNode(new VariableNode('foo'), new ScalarNode(true)))->compile($compiler = new Compiler());
        $this->assertSame('$foo = true', $compiler->source());
    }
}
