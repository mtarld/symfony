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
use Symfony\Component\SerDes\Internal\Serialize\Node\PhpDocNode;

class PhpDocNodeTest extends TestCase
{
    public function testCompile()
    {
        (new PhpDocNode(['@param string foo', '', '@return bool']))->compile($compiler = new Compiler());
        $this->assertSame(
            <<<PHP
            /**
             * @param string foo
             *
             * @return bool
             */

            PHP,
            $compiler->source(),
        );
    }
}
