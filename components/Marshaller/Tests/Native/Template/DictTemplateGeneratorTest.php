<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Node\AssignNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ForEachNode;
use Symfony\Component\Marshaller\Native\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\TemplateStringNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Native\Template\DictTemplateGenerator;
use Symfony\Component\Marshaller\Native\Template\TemplateGeneratorInterface;
use Symfony\Component\Marshaller\Native\Type\Type;

final class DictTemplateGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $templateGenerator = $this->createMock(TemplateGeneratorInterface::class);
        $templateGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(new Type('int'), new VariableNode('value_0'), ['variable_counters' => ['prefix' => 1, 'key' => 1, 'value' => 1]])
            ->willReturn([new ScalarNode('NESTED')]);

        $dictTemplateGenerator = new class ($templateGenerator) extends DictTemplateGenerator {
            protected function beforeItems(): string
            {
                return 'BEFORE_ITEMS';
            }

            protected function afterItems(): string
            {
                return 'AFTER_ITEMS';
            }

            protected function itemSeparator(): string
            {
                return 'ITEM_SEPARATOR';
            }

            protected function beforeKey(): string
            {
                return 'BEFORE_KEY';
            }

            protected function afterKey(): string
            {
                return 'AFTER_KEY';
            }

            protected function escapeKey(NodeInterface $key): NodeInterface
            {
                return new FunctionNode('KEY', [$key]);
            }
        };

        $type = new Type('array', isGeneric: true, genericParameterTypes: [new Type('string'), new Type('int')]);
        $nodes = $dictTemplateGenerator->generate($type, new VariableNode('accessor'), []);

        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('BEFORE_ITEMS')])),
            new ExpressionNode(new AssignNode(new VariableNode('prefix_0'), new ScalarNode(''))),
            new ForEachNode(new VariableNode('accessor'), 'key_0', 'value_0', [
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new TemplateStringNode(new VariableNode('prefix_0'), 'BEFORE_KEY')])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('KEY', [new VariableNode('key_0')])])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('AFTER_KEY')])),
                new ScalarNode('NESTED'),
                new ExpressionNode(new AssignNode(new VariableNode('prefix_0'), new ScalarNode('ITEM_SEPARATOR'))),
            ]),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('AFTER_ITEMS')])),
        ], $nodes);
    }
}
