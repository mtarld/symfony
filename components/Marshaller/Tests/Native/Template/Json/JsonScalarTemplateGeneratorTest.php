<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template\Json;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\TernaryConditionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Native\Template\Json\JsonScalarTemplateGenerator;
use Symfony\Component\Marshaller\Native\Type\Type;

final class JsonScalarTemplateGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $nodes = (new JsonScalarTemplateGenerator())->generate(new Type('int'), new VariableNode('accessor'), []);

        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new VariableNode('accessor')])),
        ], $nodes);
    }

    public function testGenerateString(): void
    {
        $nodes = (new JsonScalarTemplateGenerator())->generate(new Type('string'), new VariableNode('accessor'), []);

        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('"')])),
            new ExpressionNode(new FunctionNode('\fwrite', [
                new VariableNode('resource'),
                new FunctionNode('\addcslashes', [new VariableNode('accessor'), new ScalarNode('"\\')]),
            ])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('"')])),
        ], $nodes);
    }

    public function testGenerateBool(): void
    {
        $nodes = (new JsonScalarTemplateGenerator())->generate(new Type('bool'), new VariableNode('accessor'), []);

        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fwrite', [
                new VariableNode('resource'),
                new TernaryConditionNode(new VariableNode('accessor'), new ScalarNode('true'), new ScalarNode('false')),
            ])),
        ], $nodes);
    }
}
