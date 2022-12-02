<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template\Json;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Node\AssignNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ForEachNode;
use Symfony\Component\Marshaller\Native\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\TemplateStringNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Native\Template\Json\JsonDictTemplateGenerator;
use Symfony\Component\Marshaller\Native\Template\TemplateGeneratorInterface;
use Symfony\Component\Marshaller\Native\Type\Type;

final class JsonDictTemplateGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $templateGenerator = $this->createStub(TemplateGeneratorInterface::class);
        $templateGenerator->method('generate')->willReturn([new ScalarNode('NESTED')]);

        $type = new Type('array', isGeneric: true, genericParameterTypes: [new Type('string'), new Type('int')]);
        $nodes = (new JsonDictTemplateGenerator($templateGenerator))->generate($type, new VariableNode('accessor'), []);

        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('{')])),
            new ExpressionNode(new AssignNode(new VariableNode('prefix_0'), new ScalarNode(''))),
            new ForEachNode(new VariableNode('accessor'), 'key_0', 'value_0', [
                new ExpressionNode(new AssignNode(new VariableNode('key_0'), new FunctionNode('\json_encode', [new VariableNode('key_0')]))),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new TemplateStringNode(
                    new VariableNode('prefix_0'),
                    '',
                    new VariableNode('key_0'),
                    ':',
                )])),
                new ScalarNode('NESTED'),
                new ExpressionNode(new AssignNode(new VariableNode('prefix_0'), new ScalarNode(','))),
            ]),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('}')])),
        ], $nodes);
    }
}
