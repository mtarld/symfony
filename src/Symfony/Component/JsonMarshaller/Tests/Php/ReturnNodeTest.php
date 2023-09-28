<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Tests\Php;

use PHPUnit\Framework\TestCase;
use Symfony\Component\JsonMarshaller\Php\Compiler;
use Symfony\Component\JsonMarshaller\Php\ReturnNode;
use Symfony\Component\JsonMarshaller\Php\ScalarNode;

class ReturnNodeTest extends TestCase
{
    public function testCompile()
    {
        (new ReturnNode(new ScalarNode(true)))->compile($compiler = new Compiler());
        $this->assertSame('return true', $compiler->source());

        (new ReturnNode(null))->compile($compiler = new Compiler());
        $this->assertSame('return', $compiler->source());
    }
}
