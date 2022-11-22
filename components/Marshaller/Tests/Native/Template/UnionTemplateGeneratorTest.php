<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

use Symfony\Component\Marshaller\Native\Template\TemplateGeneratorInterface;
use Symfony\Component\Marshaller\Native\Template\UnionTemplateGenerator;
use Symfony\Component\Marshaller\Native\Type\Type;
use Symfony\Component\Marshaller\Native\Type\UnionType;

final class UnionTemplateGeneratorTest extends TemplateGeneratorTestCase
{
    public function testGenerate(): void
    {
        $templateGenerator = $this->createMock(TemplateGeneratorInterface::class);
        $templateGenerator
            ->expects($this->exactly(5))
            ->method('generate')
            ->withConsecutive(
                [new Type('int'), '$accessor', ['indentation_level' => 1]],
                [new Type('string'), '$accessor', ['indentation_level' => 1]],
                [new Type('int'), '$accessor', ['indentation_level' => 1]],
                [new Type('string'), '$accessor', ['indentation_level' => 1]],
                [new Type('float'), '$accessor', ['indentation_level' => 1]],
            )
            ->willReturnCallback(fn (Type $t) => 'NESTED_'.$t.PHP_EOL);

        $unionTemplateGenerator = new UnionTemplateGenerator($templateGenerator);

        $this->assertSame([
            'if (is_int($accessor)) {',
            'NESTED_int',
            '} else {',
            'NESTED_string',
            '}',
        ], $this->lines($unionTemplateGenerator->generate(new UnionType([new Type('int'), new Type('string')]), '$accessor', $this->context())));

        $this->assertSame([
            'if (is_int($accessor)) {',
            'NESTED_int',
            '} elseif (is_string($accessor)) {',
            'NESTED_string',
            '} else {',
            'NESTED_float',
            '}',
        ], $this->lines($unionTemplateGenerator->generate(new UnionType([new Type('int'), new Type('string'), new Type('float')]), '$accessor', $this->context())));
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
        $templateGenerator->method('generate')->willReturnCallback(fn (Type $t) => 'NESTED_'.$t.PHP_EOL);

        $template = (new UnionTemplateGenerator($templateGenerator))->generate(new UnionType($types), '$accessor', $this->context());

        $sortedTypes = array_values(array_map(
            fn (string $l) => str_replace('NESTED_', '', $l),
            array_filter($this->lines($template), fn (string $l) => str_starts_with($l, 'NESTED_')),
        ));

        $this->assertSame($expectedOrder, $sortedTypes);
    }

    /**
     * @return iterable<array{0: list<string>, 1: list<Type>}
     */
    public function sortTypesDataProvider(): iterable
    {
        yield [['int', 'string'], [new Type('int'), new Type('string')]];
        yield [['int', Leaf::class], [new Type('object', className: Leaf::class), new Type('int')]];
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

        (new UnionTemplateGenerator($templateGenerator))->generate(new UnionType($types), '$accessor', $this->context());

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
