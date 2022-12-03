<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

use Symfony\Component\Marshaller\Native\Ast\Node\AssignNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ForEachNode;
use Symfony\Component\Marshaller\Native\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Native\Template\ListTemplateGenerator;
use Symfony\Component\Marshaller\Native\Type\Type;

final class ListTemplateGeneratorTest extends TemplateGeneratorTestCase
{
    public function testGenerate(): void
    {
        $listTemplateGenerator = new ListTemplateGenerator(
            'BEFORE_ITEMS',
            'AFTER_ITEMS',
            'ITEM_SEPARATOR',
        );

        $type = new Type('array', isGeneric: true, genericParameterTypes: [new Type('int'), new Type('int')]);
        $nodes = $listTemplateGenerator->generate($type, new VariableNode('accessor'), [], self::createTemplateGeneratorStub());

        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('BEFORE_ITEMS')])),
            new ExpressionNode(new AssignNode(new VariableNode('prefix_0'), new ScalarNode(''))),
            new ForEachNode(new VariableNode('accessor'), null, 'value_0', [
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new VariableNode('prefix_0')])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new VariableNode('value_0')])),
                new ExpressionNode(new AssignNode(new VariableNode('prefix_0'), new ScalarNode('ITEM_SEPARATOR'))),
            ]),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('AFTER_ITEMS')])),
        ], $nodes);
    }
}
