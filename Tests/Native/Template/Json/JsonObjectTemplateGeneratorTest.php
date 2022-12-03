<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template\Json;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Node\AssignNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Native\Template\Json\JsonObjectTemplateGenerator;
use Symfony\Component\Marshaller\Native\Template\TemplateGenerator;
use Symfony\Component\Marshaller\Native\Type\Type;
use Symfony\Component\Marshaller\Tests\Fixtures\ClassicDummy;

final class JsonObjectTemplateGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $templateGenerator = $this->createStub(TemplateGenerator::class);
        $templateGenerator->method('generate')->willReturn([new ScalarNode('NESTED')]);

        $type = new Type('object', className: ClassicDummy::class);
        $nodes = (new JsonObjectTemplateGenerator($templateGenerator))->generate($type, new VariableNode('accessor'), []);

        $this->assertEquals([
            new ExpressionNode(new AssignNode(new VariableNode('object_0'), new VariableNode('accessor'))),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('{')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('"')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('id')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('":')])),
            new ScalarNode('NESTED'),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode(',')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('"')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('name')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('":')])),
            new ScalarNode('NESTED'),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('}')])),
        ], $nodes);
    }
}
