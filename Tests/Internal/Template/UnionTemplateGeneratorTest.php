<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Template;

use Symfony\Component\Marshaller\Exception\LogicException;
use Symfony\Component\Marshaller\Internal\Ast\Node\AssignNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\IfNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Internal\Ast\Node\RawNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Internal\Template\UnionTemplateGenerator;
use Symfony\Component\Marshaller\Internal\Type\Type;
use Symfony\Component\Marshaller\Internal\Type\UnionType;

final class UnionTemplateGeneratorTest extends TemplateGeneratorTestCase
{
    public function testGenerate(): void
    {
        $context = [
            'hooks' => [
                'type' => static function (string $type, string $accessor, array $context): array {
                    return ['type' => $type, 'accessor' => $type, 'context' => $context];
                },
            ],
        ];

        $unionTemplateGenerator = new UnionTemplateGenerator(self::createTemplateGeneratorStub());

        $this->assertEquals([
            new IfNode(
                (new Type('int'))->validator(new VariableNode('accessor')),
                [new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new RawNode('int')]))],
                [new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new RawNode('string')]))],
            ),
        ], $unionTemplateGenerator->generate(new UnionType([new Type('int'), new Type('string')]), new VariableNode('accessor'), $context));

        $this->assertEquals([
            new IfNode(
                (new Type('int'))->validator(new VariableNode('accessor')),
                [new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new RawNode('int')]))],
                [new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new RawNode('float')]))],
                [[
                    'condition' => (new Type('string'))->validator(new VariableNode('accessor')),
                    'body' => [new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new RawNode('string')]))],
                ]]
            ),
        ], $unionTemplateGenerator->generate(new UnionType([new Type('int'), new Type('string'), new Type('float')]), new VariableNode('accessor'), $context));
    }

    /**
     * @dataProvider sortTypesDataProvider
     *
     * @param list<string> $expectedOrder
     * @param list<Type>   $types
     */
    public function testSortTypes(array $expectedOrder, array $types): void
    {
        $context = [
            'hooks' => [
                'type' => static function (string $type, string $accessor, array $context): array {
                    return ['type' => $type, 'accessor' => $type, 'context' => $context];
                },
            ],
        ];

        $nodes = (new UnionTemplateGenerator(self::createTemplateGeneratorStub()))->generate(new UnionType($types), new VariableNode('accessor'), $context);

        if (\count($expectedOrder) <= 1) {
            $this->assertSame(array_map(fn (ExpressionNode $n): string => $n->node->parameters[1]->source, $nodes), $expectedOrder);

            return;
        }

        $extractType = static function (array $nodes): string {
            $filteredNodes = array_filter($nodes, static function (NodeInterface $n): bool {
                if (!$n instanceof ExpressionNode) {
                    return false;
                }

                $n = $n->node;

                if ($n instanceof FunctionNode) {
                    return '\fwrite' === $n->name && $n->parameters[1] instanceof RawNode;
                }

                if ($n instanceof AssignNode) {
                    return $n->right instanceof RawNode;
                }

                return false;
            });

            return array_map(static function (ExpressionNode $n): string {
                if ($n->node instanceof FunctionNode) {
                    return $n->node->parameters[1]->source;
                }

                return $n->node->right->source;
            }, $filteredNodes)[0];
        };

        $sortedTypes = [
            $extractType($nodes[0]->onIf),
            ...array_map(fn (array $e): string => $extractType($e['body']), $nodes[0]->elseIfs),
            $extractType($nodes[0]->onElse),
        ];

        $this->assertSame($expectedOrder, $sortedTypes);
    }

    /**
     * @return iterable<array{0: list<string>, 1: list<Type>}
     */
    public function sortTypesDataProvider(): iterable
    {
        yield [['int', 'string'], [new Type('int'), new Type('string')]];
        yield [['int'], [new Type('int'), new Type('int')]];
        yield [['int', Leaf::class], [new Type('object', className: Leaf::class), new Type('int')]];

        return;
        yield [[Leaf::class], [new Type('object', className: Leaf::class), new Type('object', className: Leaf::class)]];
        yield [
            [Leaf::class, Branch::class, Root::class],
            [new Type('object', className: Branch::class), new Type('object', className: Root::class), new Type('object', className: Leaf::class)],
        ];
        yield [
            [Branch::class, Root::class],
            [new Type('object', className: Root::class), new Type('object', className: Branch::class)],
        ];
        yield [
            ['int', Leaf::class, Root::class],
            [new Type('object', className: Leaf::class), new Type('object', className: Root::class), new Type('int')],
        ];
    }

    /**
     * @dataProvider throwIfSameHierarchicalLevelDataProvider
     *
     * @param list<Type> $types
     */
    public function testThrowIfSameHierarchicalLevel(bool $expectException, array $types): void
    {
        if ($expectException) {
            $this->expectException(LogicException::class);
        }

        (new UnionTemplateGenerator(self::createTemplateGeneratorStub()))->generate(new UnionType($types), new VariableNode('accessor'), []);

        $this->addToAssertionCount(1);
    }

    /**
     * @return iterable<array{0: bool, 1: list<Type>}
     */
    public function throwIfSameHierarchicalLevelDataProvider(): iterable
    {
        yield [false, [new Type('object', className: Leaf::class), new Type('int')]];
        yield [false, [new Type('object', className: Branch::class), new Type('object', className: Root::class), new Type('object', className: Leaf::class)]];
        yield [false, [new Type('object', className: Leaf::class), new Type('object', className: Root::class)]];
        yield [true, [new Type('object', className: Leaf::class), new Type('object', className: Leaf2::class)]];
        yield [true, [new Type('object', className: Leaf::class), new Type('object', className: Branch2::class)]];
    }
}

abstract class Root
{
}

abstract class Branch extends Root
{
}

abstract class Branch2 extends Root
{
}

final class Leaf extends Branch
{
}

final class Leaf2 extends Branch2
{
}
