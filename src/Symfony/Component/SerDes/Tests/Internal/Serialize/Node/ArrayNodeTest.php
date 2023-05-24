<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Internal\Serialize\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Internal\Serialize\Compiler;
use Symfony\Component\SerDes\Internal\Serialize\Node\ArrayNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ScalarNode;

class ArrayNodeTest extends TestCase
{
    public function testCompile()
    {
        (new ArrayNode([]))->compile($compiler = new Compiler());
        $this->assertSame('[]', $compiler->source());

        (new ArrayNode([new ScalarNode('foo'), new ScalarNode('bar')]))->compile($compiler = new Compiler());
        $this->assertSame('["foo", "bar"]', $compiler->source());

        (new ArrayNode(['foo' => new ScalarNode('bar')]))->compile($compiler = new Compiler());
        $this->assertSame('["foo" => "bar"]', $compiler->source());
    }
}
