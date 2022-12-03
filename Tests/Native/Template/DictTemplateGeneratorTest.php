<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

use Symfony\Component\Marshaller\Native\Ast\Node\AssignNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ForEachNode;
use Symfony\Component\Marshaller\Native\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\TemplateStringNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Native\Template\DictTemplateGenerator;
use Symfony\Component\Marshaller\Native\Type\Type;

final class DictTemplateGeneratorTest extends TemplateGeneratorTestCase
{
    public function testGenerate(): void
    {
        $dictTemplateGenerator = new DictTemplateGenerator(
            'BEFORE_ITEMS',
            'AFTER_ITEMS',
            'ITEM_SEPARATOR',
            'BEFORE_KEY',
            'AFTER_KEY',
            fn (NodeInterface $key) => new FunctionNode('KEY', [$key]),
        );

        $type = new Type('array', isGeneric: true, genericParameterTypes: [new Type('string'), new Type('int')]);
        $nodes = $dictTemplateGenerator->generate($type, new VariableNode('accessor'), [], self::createTemplateGeneratorStub());

        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('BEFORE_ITEMS')])),
            new ExpressionNode(new AssignNode(new VariableNode('prefix_0'), new ScalarNode(''))),
            new ForEachNode(new VariableNode('accessor'), 'key_0', 'value_0', [
                new ExpressionNode(new AssignNode(new VariableNode('key_0'), new FunctionNode('KEY', [new VariableNode('key_0')]))),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new TemplateStringNode(
                    new VariableNode('prefix_0'),
                    'BEFORE_KEY',
                    new VariableNode('key_0'),
                    'AFTER_KEY',
                )])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new VariableNode('value_0')])),
                new ExpressionNode(new AssignNode(new VariableNode('prefix_0'), new ScalarNode('ITEM_SEPARATOR'))),
            ]),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('AFTER_ITEMS')])),
        ], $nodes);
    }
}
