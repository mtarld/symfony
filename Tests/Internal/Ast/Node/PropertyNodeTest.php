<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Internal\Ast\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Node\PropertyNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\VariableNode;

final class PropertyNodeTest extends TestCase
{
    public function testCompile(): void
    {
        (new PropertyNode(new VariableNode('foo'), 'bar'))->compile($compiler = new Compiler());
        $this->assertSame('$foo->bar', $compiler->source());
    }
}
