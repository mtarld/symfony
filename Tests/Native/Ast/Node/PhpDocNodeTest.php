<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Ast\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Node\PhpDocNode;

final class PhpDocNodeTest extends TestCase
{
    public function testCompile(): void
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
