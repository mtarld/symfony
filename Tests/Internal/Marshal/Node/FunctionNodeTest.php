<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Marshal\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Marshal\Compiler;
use Symfony\Component\Marshaller\Internal\Marshal\Node\FunctionNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ScalarNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\VariableNode;

final class FunctionNodeTest extends TestCase
{
    public function testCompile(): void
    {
        (new FunctionNode('fooFunction', [new VariableNode('foo'), new ScalarNode(true)]))->compile($compiler = new Compiler());
        $this->assertSame('fooFunction($foo, true)', $compiler->source());
    }
}
