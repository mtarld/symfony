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
use Symfony\Component\Marshaller\Internal\Marshal\Node\AssignNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ScalarNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\VariableNode;

final class AssignNodeTest extends TestCase
{
    public function testCompile(): void
    {
        (new AssignNode(new VariableNode('foo'), new ScalarNode(true)))->compile($compiler = new Compiler());
        $this->assertSame('$foo = true', $compiler->source());
    }
}
