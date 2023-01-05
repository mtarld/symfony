<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Ast\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Node\RawNode;

final class RawNodeTest extends TestCase
{
    public function testCompile(): void
    {
        (new RawNode('echo "raw php";'))->compile($compiler = new Compiler());
        $this->assertSame('echo "raw php";', $compiler->source());
    }
}
