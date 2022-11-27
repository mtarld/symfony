<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Node\BinaryNode;
use Symfony\Component\Marshaller\Native\Ast\Node\IfNode;
use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Native\Ast\Node\RawNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Native\Template\DictTemplateGenerator;
use Symfony\Component\Marshaller\Native\Template\ListTemplateGenerator;
use Symfony\Component\Marshaller\Native\Template\NullTemplateGenerator;
use Symfony\Component\Marshaller\Native\Template\ObjectTemplateGenerator;
use Symfony\Component\Marshaller\Native\Template\ScalarTemplateGenerator;
use Symfony\Component\Marshaller\Native\Template\TemplateGenerator;
use Symfony\Component\Marshaller\Native\Template\UnionTemplateGenerator;
use Symfony\Component\Marshaller\Native\Type\Type;
use Symfony\Component\Marshaller\Native\Type\UnionType;
use Symfony\Component\Marshaller\Tests\Fixtures\ClassicDummy;

final class TemplateGeneratorTest extends TestCase
{
    /**
     * @dataProvider generateValueTemplateDataProvider
     *
     * @param list<NodeInterface> $expectedLines
     */
    public function testGenerateValueTemplate(array $expectedNodes, Type|UnionType $type): void
    {
        $this->assertEquals($expectedNodes, $this->createGenerator()->generate($type, new VariableNode('accessor'), []));
    }

    /**
     * @return iterable<array{0: list<NodeInterface>, 1: Type|UnionType}
     */
    public function generateValueTemplateDataProvider(): iterable
    {
        yield [[], new UnionType([])];
        yield [[new ScalarNode('NULL')], new Type('null')];
        yield [[new ScalarNode('SCALAR')], new Type('int')];
        yield [[new ScalarNode('OBJECT')], new Type('object', className: ClassicDummy::class)];
        yield [[new ScalarNode('LIST')], new Type('array', isGeneric: true, genericParameterTypes: [new Type('int'), new Type('int')])];
        yield [[new ScalarNode('DICT')], new Type('array', isGeneric: true, genericParameterTypes: [new Type('string'), new Type('int')])];
    }

    public function testThrowOnInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown "foo" type.');

        $this->createGenerator()->generate(new Type('foo'), new VariableNode('accessor'), []);
    }

    public function testGenerateForNullable(): void
    {
        $nodes = $this->createGenerator()->generate(new Type('int', isNullable: true), new VariableNode('accessor'), []);

        $this->assertEquals([
            new IfNode(
                new BinaryNode('===', new ScalarNode(null), new VariableNode('accessor')),
                [new ScalarNode('NULL')],
                [new ScalarNode('SCALAR')],
            ),
        ], $nodes);
    }

    /**
     * @dataProvider throwWhenInvalidHookResultDataProvider
     *
     * @param array{type: string, accessor: string, context: array<string, mixed>} $hookResult
     */
    public function testThrowWhenInvalidHookResult(string $expectedExceptionMessage, array $hookResult): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->createGenerator()->generate(new Type('int'), new VariableNode('accessor'), [
            'hooks' => [
                'type' => static function (string $type, string $accessor, array $context) use ($hookResult): array {
                    return $hookResult;
                },
            ],
        ]);
    }

    /**
     * @return iterable<array{0: string, 1: array{type: string, accessor: string, context: array<string, mixed>}}>
     */
    public function throwWhenInvalidHookResultDataProvider(): iterable
    {
        yield ['Hook array result is missing "type".', ['accessor' => '$accessor', 'context' => []]];
        yield ['Hook array result\'s "type" must be a "string".', ['type' => 1, 'accessor' => '$accessor', 'context' => []]];
        yield ['Hook array result is missing "accessor".', ['type' => 'int', 'context' => []]];
        yield ['Hook array result\'s "accessor" must be a "string".', ['type' => 'int', 'accessor' => 1, 'context' => []]];
        yield ['Hook array result is missing "context".', ['type' => 'int', 'accessor' => '$accessor']];
        yield ['Hook array result\'s "context" must be an "array".', ['type' => 'int', 'accessor' => '$accessor', 'context' => 1]];
    }

    public function testReplaceTypeAccessorAndContextWithHook(): void
    {
        $context = [
            'hooks' => [
                'type' => static function (string $type, string $accessor, array $context): array {
                    return [
                        'type' => 'string',
                        'accessor' => '$ACCESSOR',
                        'context' => ['CONTEXT'],
                    ];
                },
            ],
        ];

        $scalarGenerator = $this->createMock(ScalarTemplateGenerator::class);
        $scalarGenerator
            ->expects($this->exactly(3))
            ->method('generate')
            ->with(new Type('string'), new RawNode('$ACCESSOR'), ['CONTEXT'])
            ->willReturn([new ScalarNode('SCALAR')]);

        $generator = $this->createGenerator(scalarGenerator: $scalarGenerator);

        $generator->generate(new Type('int'), new VariableNode('accessor'), $context);
        $generator->generate(new Type('int', isNullable: true), new VariableNode('accessor'), $context);
    }

    public function testTypeHookArguments(): void
    {
        $hookCallCount = 0;

        $context = [
            'hooks' => [
                'int' => function (string $type, string $accessor, array $context) use (&$hookCallCount): array {
                    ++$hookCallCount;

                    $this->assertSame('?int', $type);
                    $this->assertSame('$accessor', $accessor);
                    $this->assertCount(1, $context);
                    $this->assertArrayHasKey('hooks', $context);

                    return ['type' => $type, 'accessor' => $accessor, 'context' => $context];
                },
                'null' => function (string $type, string $accessor, array $context) use (&$hookCallCount): array {
                    ++$hookCallCount;

                    $this->assertSame('null', $type);
                    $this->assertSame('null', $accessor);
                    $this->assertCount(1, $context);
                    $this->assertArrayHasKey('hooks', $context);

                    return ['type' => $type, 'accessor' => $accessor, 'context' => $context];
                },
            ],
        ];

        $this->createGenerator()->generate(new Type('int', isNullable: true), new VariableNode('accessor'), $context);

        $this->assertSame(2, $hookCallCount);
    }

    public function testCheckForCircularReferences(): void
    {
        $generator = $this->createGenerator();

        $generator->generate(new Type('object', className: 'foo'), new VariableNode('accessor'), []);
        $this->addToAssertionCount(1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Circular reference detected on "foo" detected.');

        $generator->generate(new Type('object', className: 'foo'), new VariableNode('accessor'), ['generated_classes' => ['foo' => true]]);
    }

    private function createGenerator(
        ScalarTemplateGenerator $scalarGenerator = null,
        NullTemplateGenerator $nullGenerator = null,
        ObjectTemplateGenerator $objectGenerator = null,
        ListTemplateGenerator $listGenerator = null,
        DictTemplateGenerator $dictGenerator = null,
    ): TemplateGenerator {
        if (null === $scalarGenerator) {
            $scalarGenerator = $this->createStub(ScalarTemplateGenerator::class);
            $scalarGenerator->method('generate')->willReturn([new ScalarNode('SCALAR')]);
        }

        if (null === $objectGenerator) {
            $nullGenerator = $this->createStub(NullTemplateGenerator::class);
            $nullGenerator->method('generate')->willReturn([new ScalarNode('NULL')]);
        }

        if (null === $objectGenerator) {
            $objectGenerator = $this->createStub(ObjectTemplateGenerator::class);
            $objectGenerator->method('generate')->willReturn([new ScalarNode('OBJECT')]);
        }

        if (null === $listGenerator) {
            $listGenerator = $this->createStub(ListTemplateGenerator::class);
            $listGenerator->method('generate')->willReturn([new ScalarNode('LIST')]);
        }

        if (null === $dictGenerator) {
            $dictGenerator = $this->createStub(DictTemplateGenerator::class);
            $dictGenerator->method('generate')->willReturn([new ScalarNode('DICT')]);
        }

        return new class (
            scalarGenerator: $scalarGenerator,
            nullGenerator: $nullGenerator,
            objectGenerator: $objectGenerator,
            listGenerator: $listGenerator,
            dictGenerator: $dictGenerator,
            unionGenerator: new UnionTemplateGenerator($this->createStub(TemplateGenerator::class)),
            format: 'FORMAT',
        ) extends TemplateGenerator {
        };
    }
}
