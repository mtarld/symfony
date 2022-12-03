<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

use Symfony\Component\Marshaller\Native\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Native\Template\ScalarTemplateGenerator;
use Symfony\Component\Marshaller\Native\Type\Type;

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
