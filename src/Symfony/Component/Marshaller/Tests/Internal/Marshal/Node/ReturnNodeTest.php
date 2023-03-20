<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Marshal\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Marshal\Compiler;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ReturnNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ScalarNode;

class ReturnNodeTest extends TestCase
{
    public function testCompile()
    {
        (new ReturnNode(new ScalarNode(true)))->compile($compiler = new Compiler());
        $this->assertSame('return true', $compiler->source());
    }
}
