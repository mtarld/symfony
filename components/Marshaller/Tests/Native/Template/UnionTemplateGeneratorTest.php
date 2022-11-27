<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Node\IfNode;
use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Native\Template\TemplateGeneratorInterface;
use Symfony\Component\Marshaller\Native\Template\UnionTemplateGenerator;
use Symfony\Component\Marshaller\Native\Type\Type;
use Symfony\Component\Marshaller\Native\Type\UnionType;

final class UnionTemplateGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $templateGenerator = $this->createMock(TemplateGeneratorInterface::class);
        $templateGenerator
            ->expects($this->exactly(5))
            ->method('generate')
            ->withConsecutive(
                [new Type('int'), new VariableNode('accessor'), []],
                [new Type('string'), new VariableNode('accessor'), []],
                [new Type('string'), new VariableNode('accessor'), []],
                [new Type('int'), new VariableNode('accessor'), []],
                [new Type('float'), new VariableNode('accessor'), []],
            )
            ->willReturnCallback(fn (Type $t): array => [new ScalarNode('NESTED_'.strtoupper((string) $t))]);

        $unionTemplateGenerator = new UnionTemplateGenerator($templateGenerator);

        $this->assertEquals([
            new IfNode(
                (new Type('int'))->validator(new VariableNode('accessor')),
                [new ScalarNode('NESTED_INT')],
                [new ScalarNode('NESTED_STRING')],
            ),
        ], $unionTemplateGenerator->generate(new UnionType([new Type('int'), new Type('string')]), new VariableNode('accessor'), []));

        $this->assertEquals([
            new IfNode(
                (new Type('int'))->validator(new VariableNode('accessor')),
                [new ScalarNode('NESTED_INT')],
                [new ScalarNode('NESTED_FLOAT')],
                [[
                    'condition' => (new Type('string'))->validator(new VariableNode('accessor')),
                    'body' => [new ScalarNode('NESTED_STRING')],
                ]]
            ),
        ], $unionTemplateGenerator->generate(new UnionType([new Type('int'), new Type('string'), new Type('float')]), new VariableNode('accessor'), []));
    }

    /**
     * @dataProvider sortTypesDataProvider
     *
     * @param list<string> $expectedOrder
     * @param list<Type>   $types
     */
    public function testSortTypes(array $expectedOrder, array $types): void
    {
        $templateGenerator = $this->createStub(TemplateGeneratorInterface::class);
        $templateGenerator->method('generate')->willReturnCallback(fn (Type $t): array => [new ScalarNode('NESTED_'.$t)]);

        $nodes = (new UnionTemplateGenerator($templateGenerator))->generate(new UnionType($types), new VariableNode('accessor'), []);

        if (\count($expectedOrder) <= 1) {
            $this->assertSame(array_map(fn (NodeInterface $t) => str_replace('NESTED_', '', $t->value), $nodes), $expectedOrder);

            return;
        }

        $sortedTypes = array_map(
            fn (string $t) => str_replace('NESTED_', '', $t),
            [
                $nodes[0]->onIf[0]->value,
                ...array_map(fn (array $e): string => $e['body'][0]->value, $nodes[0]->elseIfs),
                $nodes[0]->onElse[0]->value,
            ],
        );

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
        $templateGenerator = $this->createStub(TemplateGeneratorInterface::class);

        if ($expectException) {
            $this->expectException(\RuntimeException::class);
        }

        (new UnionTemplateGenerator($templateGenerator))->generate(new UnionType($types), new VariableNode('accessor'), []);

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
