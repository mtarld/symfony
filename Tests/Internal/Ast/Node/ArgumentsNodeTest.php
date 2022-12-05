<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Internal\Ast\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Node\ArgumentsNode;

final class ArgumentsNodeTest extends TestCase
{
    public function testCompile(): void
    {
        (new ArgumentsNode(['foo' => '?int']))->compile($compiler = new Compiler());
        $this->assertSame('?int $foo', $compiler->source());

        (new ArgumentsNode(['foo' => 'string', 'bar' => null]))->compile($compiler = new Compiler());
        $this->assertSame('string $foo, $bar', $compiler->source());
    }
}
