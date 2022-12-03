<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Node\AssignNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\PropertyNode;
use Symfony\Component\Marshaller\Native\Ast\Node\RawNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Native\Template\ObjectTemplateGenerator;
use Symfony\Component\Marshaller\Native\Template\TemplateGeneratorInterface;
use Symfony\Component\Marshaller\Native\Type\Type;
use Symfony\Component\Marshaller\Tests\Fixtures\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\ConstructorPropertyPromotedDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\DummyWithNotPublicProperty;

final class ObjectTemplateGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $templateGenerator = $this->createMock(TemplateGeneratorInterface::class);
        $templateGenerator
            ->expects($this->exactly(2))
            ->method('generate')
            ->withConsecutive(
                [new Type('int'), new PropertyNode(new VariableNode('object_0'), 'id'), ['variable_counters' => ['object' => 1]]],
                [new Type('string'), new PropertyNode(new VariableNode('object_0'), 'name'), ['variable_counters' => ['object' => 1]]],
            )
            ->willReturn([new ScalarNode('NESTED')]);

        $nodes = $this->createObjectGenerator($templateGenerator)->generate(new Type('object', className: ClassicDummy::class), new VariableNode('accessor'), []);

        $this->assertEquals([
            new ExpressionNode(new AssignNode(new VariableNode('object_0'), new VariableNode('accessor'))),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('BEFORE_PROPERTIES')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('BEFORE_PROPERTY_NAME')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('ESCAPE(id)')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('AFTER_PROPERTY_NAME')])),
            new ScalarNode('NESTED'),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('PROPERTY_SEPARATOR')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('BEFORE_PROPERTY_NAME')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('ESCAPE(name)')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('AFTER_PROPERTY_NAME')])),
            new ScalarNode('NESTED'),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('AFTER_PROPERTIES')])),
        ], $nodes);
    }

    public function testGenerateWithConstructorPropertyPromotion(): void
    {
        $templateGenerator = $this->createMock(TemplateGeneratorInterface::class);
        $templateGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(new Type('int'), new PropertyNode(new VariableNode('object_0'), 'id'), ['variable_counters' => ['object' => 1]])
            ->willReturn([new ScalarNode('NESTED')]);

        $nodes = $this->createObjectGenerator($templateGenerator)->generate(new Type('object', className: ConstructorPropertyPromotedDummy::class), new VariableNode('accessor'), []);

        $this->assertEquals([
            new ExpressionNode(new AssignNode(new VariableNode('object_0'), new VariableNode('accessor'))),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('BEFORE_PROPERTIES')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('BEFORE_PROPERTY_NAME')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('ESCAPE(id)')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('AFTER_PROPERTY_NAME')])),
            new ScalarNode('NESTED'),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('AFTER_PROPERTIES')])),
        ], $nodes);
    }

    public function testThrowWhenPropertyIsNotPublic(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('"%s::$name" must be public.', DummyWithNotPublicProperty::class));

        $this->createObjectGenerator()->generate(new Type('object', className: DummyWithNotPublicProperty::class), new VariableNode('accessor'), []);
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

        $this->createObjectGenerator()->generate(new Type('object', className: ClassicDummy::class), new VariableNode('accessor'), [
            'hooks' => [
                'property' => static function (\ReflectionProperty $property, string $accessor, array $context) use ($hookResult): array {
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
        yield ['Hook array result is missing "name".', ['type' => 'int', 'accessor' => '$accessor', 'context' => []]];
        yield ['Hook array result\'s "name" must be a "string".', ['name' => 1, 'type' => 'int', 'accessor' => '$accessor', 'context' => []]];
        yield ['Hook array result is missing "type".', ['name' => 'name', 'accessor' => '$accessor', 'context' => []]];
        yield ['Hook array result\'s "type" must be a "string".', ['name' => 'name', 'type' => 1, 'accessor' => '$accessor', 'context' => []]];
        yield ['Hook array result is missing "accessor".', ['name' => 'name', 'type' => 'int', 'context' => []]];
        yield ['Hook array result\'s "accessor" must be a "string".', ['name' => 'name', 'type' => 'int', 'accessor' => 1, 'context' => []]];
        yield ['Hook array result is missing "context".', ['name' => 'name', 'type' => 'int', 'accessor' => '$accessor']];
        yield ['Hook array result\'s "context" must be an "array".', ['name' => 'name', 'type' => 'int', 'accessor' => '$accessor', 'context' => 1]];
    }

    public function testReplaceNameTypeAccessorAndContextWithHook(): void
    {
        $context = [
            'hooks' => [
                'property' => static function (\ReflectionProperty $property, string $accessor, array $context): array {
                    return [
                        'name' => 'NAME',
                        'type' => 'string',
                        'accessor' => '$ACCESSOR',
                        'context' => ['CONTEXT'],
                    ];
                },
            ],
        ];

        $templateGenerator = $this->createMock(TemplateGeneratorInterface::class);
        $templateGenerator
            ->expects($this->exactly(2))
            ->method('generate')
            ->with(new Type('string'), new RawNode('$ACCESSOR'), ['CONTEXT'])
            ->willReturn([new ScalarNode('NESTED')]);

        $nodes = $this->createObjectGenerator($templateGenerator)->generate(new Type('object', className: ClassicDummy::class), new VariableNode('accessor'), $context);

        $this->assertEquals([
            new ExpressionNode(new AssignNode(new VariableNode('object_0'), new VariableNode('accessor'))),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('BEFORE_PROPERTIES')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('BEFORE_PROPERTY_NAME')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('ESCAPE(NAME)')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('AFTER_PROPERTY_NAME')])),
            new ScalarNode('NESTED'),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('PROPERTY_SEPARATOR')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('BEFORE_PROPERTY_NAME')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('ESCAPE(NAME)')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('AFTER_PROPERTY_NAME')])),
            new ScalarNode('NESTED'),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('AFTER_PROPERTIES')])),
        ], $nodes);
    }

    public function testPropertyHookArguments(): void
    {
        $hookCallCount = 0;

        $context = [
            'hooks' => [
                sprintf('%s::$id', ClassicDummy::class) => function (\ReflectionProperty $property, string $accessor, array $context) use (&$hookCallCount): array {
                    ++$hookCallCount;

                    $this->assertEquals(new \ReflectionProperty(ClassicDummy::class, 'id'), $property);
                    $this->assertSame('$object_0->id', $accessor);
                    $this->assertCount(2, $context);
                    $this->assertSame(['object' => 1], $context['variable_counters']);
                    $this->assertArrayHasKey('hooks', $context);

                    return [
                        'name' => 'name',
                        'type' => 'type',
                        'accessor' => 'accessor',
                        'context' => [],
                    ];
                },
                sprintf('%s::$name', ClassicDummy::class) => function (\ReflectionProperty $property, string $accessor, array $context) use (&$hookCallCount): array {
                    ++$hookCallCount;

                    $this->assertEquals(new \ReflectionProperty(ClassicDummy::class, 'name'), $property);
                    $this->assertSame('$object_0->name', $accessor);
                    $this->assertCount(2, $context);
                    $this->assertSame(['object' => 1], $context['variable_counters']);
                    $this->assertArrayHasKey('hooks', $context);

                    return [
                        'name' => 'name',
                        'type' => 'type',
                        'accessor' => 'accessor',
                        'context' => [],
                    ];
                },
            ],
        ];

        $this->createObjectGenerator()->generate(new Type('object', className: ClassicDummy::class), new VariableNode('accessor'), $context);

        $this->assertSame(2, $hookCallCount);
    }

    private function createObjectGenerator(TemplateGeneratorInterface $templateGenerator = null): ObjectTemplateGenerator
    {
        $templateGenerator = $templateGenerator ?? $this->createStub(TemplateGeneratorInterface::class);

        return new class ($templateGenerator) extends ObjectTemplateGenerator {
            protected function beforeProperties(): string
            {
                return 'BEFORE_PROPERTIES';
            }

            protected function afterProperties(): string
            {
                return 'AFTER_PROPERTIES';
            }

            protected function propertySeparator(): string
            {
                return 'PROPERTY_SEPARATOR';
            }

            protected function beforePropertyName(): string
            {
                return 'BEFORE_PROPERTY_NAME';
            }

            protected function afterPropertyName(): string
            {
                return 'AFTER_PROPERTY_NAME';
            }

            protected function escapeString(string $string): string
            {
                return sprintf('ESCAPE(%s)', $string);
            }
        };
    }
}
