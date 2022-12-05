<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Internal\Ast\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Node\ReturnNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\ScalarNode;

final class ReturnNodeTest extends TestCase
{
    public function testCompile(): void
    {
        (new ReturnNode(new ScalarNode(true)))->compile($compiler = new Compiler());
        $this->assertSame('return true', $compiler->source());
    }
}
