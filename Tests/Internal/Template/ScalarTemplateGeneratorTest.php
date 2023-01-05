<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Template;

use Symfony\Component\Marshaller\Internal\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Internal\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Internal\Template\ScalarTemplateGenerator;
use Symfony\Component\Marshaller\Internal\Type\Type;

final class ScalarTemplateGeneratorTest extends TemplateGeneratorTestCase
{
    public function testGenerate(): void
    {
        $scalarTemplateGenerator = new ScalarTemplateGenerator(fn (NodeInterface $n) => new ScalarNode('SCALAR'));

        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('SCALAR')])),
        ], $scalarTemplateGenerator->generate(new Type('int'), new VariableNode('accessor'), []));
    }
}
