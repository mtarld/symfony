<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json\Tests\Php;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Json\Php\Compiler;
use Symfony\Component\Json\Php\ParametersNode;

class ParametersNodeTest extends TestCase
{
    public function testCompile()
    {
        (new ParametersNode(['foo' => '?int']))->compile($compiler = new Compiler());
        $this->assertSame('?int $foo', $compiler->source());

        (new ParametersNode(['foo' => 'string', '&bar' => null]))->compile($compiler = new Compiler());
        $this->assertSame('string $foo, &$bar', $compiler->source());
    }
}
