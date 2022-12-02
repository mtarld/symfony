<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Ast\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Node\ReturnNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;

final class ReturnNodeTest extends TestCase
{
    public function testCompile(): void
    {
        (new ReturnNode(new ScalarNode(true)))->compile($compiler = new Compiler());
        $this->assertSame('return true', $compiler->source());
    }
}
