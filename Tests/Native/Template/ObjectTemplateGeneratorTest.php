<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

use Symfony\Component\Marshaller\Native\Ast\Node\AssignNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\PropertyNode;
use Symfony\Component\Marshaller\Native\Ast\Node\RawNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Native\Template\ObjectTemplateGenerator;
use Symfony\Component\Marshaller\Native\Type\Type;
use Symfony\Component\Marshaller\Tests\Fixtures\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\ConstructorPropertyPromotedDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\DummyWithNotPublicProperty;

final class ObjectTemplateGeneratorTest extends TemplateGeneratorTestCase
{
    public function testGenerate(): void
    {
        $generator = self::createObjectTemplateGenerator();
        $nodes = $generator->generate(new Type('object', className: ClassicDummy::class), new VariableNode('accessor'), [], self::createTemplateGeneratorStub());

        $this->assertEquals([
            new ExpressionNode(new AssignNode(new VariableNode('object_0'), new VariableNode('accessor'))),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('BEFORE_PROPERTIES')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('BEFORE_PROPERTY_NAME')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('ESCAPE(id)')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('AFTER_PROPERTY_NAME')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new PropertyNode(new VariableNode('object_0'), 'id')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('PROPERTY_SEPARATOR')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('BEFORE_PROPERTY_NAME')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('ESCAPE(name)')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('AFTER_PROPERTY_NAME')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new PropertyNode(new VariableNode('object_0'), 'name')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('AFTER_PROPERTIES')])),
        ], $nodes);
    }

    public function testGenerateWithConstructorPropertyPromotion(): void
    {
        $generator = self::createObjectTemplateGenerator();
        $nodes = $generator->generate(new Type('object', className: ConstructorPropertyPromotedDummy::class), new VariableNode('accessor'), [], self::createTemplateGeneratorStub());

        $this->assertEquals([
            new ExpressionNode(new AssignNode(new VariableNode('object_0'), new VariableNode('accessor'))),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('BEFORE_PROPERTIES')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('BEFORE_PROPERTY_NAME')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('ESCAPE(id)')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('AFTER_PROPERTY_NAME')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new PropertyNode(new VariableNode('object_0'), 'id')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('AFTER_PROPERTIES')])),
        ], $nodes);
    }

    public function testThrowWhenPropertyIsNotPublic(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('"%s::$name" must be public.', DummyWithNotPublicProperty::class));

        self::createObjectTemplateGenerator()->generate(
            new Type('object', className: DummyWithNotPublicProperty::class),
            new VariableNode('accessor'),
            [],
            self::createTemplateGeneratorStub(),
        );
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

        self::createObjectTemplateGenerator()->generate(new Type('object', className: ClassicDummy::class), new VariableNode('accessor'), [
            'hooks' => [
                'property' => static function (\ReflectionProperty $property, string $accessor, array $context) use ($hookResult): array {
                    return $hookResult;
                },
            ],
        ], self::createTemplateGeneratorStub());
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

        $generator = self::createObjectTemplateGenerator();
        $nodes = $generator->generate(new Type('object', className: ClassicDummy::class), new VariableNode('accessor'), $context, self::createTemplateGeneratorStub());

        $this->assertEquals([
            new ExpressionNode(new AssignNode(new VariableNode('object_0'), new VariableNode('accessor'))),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('BEFORE_PROPERTIES')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('BEFORE_PROPERTY_NAME')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('ESCAPE(NAME)')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('AFTER_PROPERTY_NAME')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new RawNode('$ACCESSOR')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('PROPERTY_SEPARATOR')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('BEFORE_PROPERTY_NAME')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('ESCAPE(NAME)')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('AFTER_PROPERTY_NAME')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new RawNode('$ACCESSOR')])),
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
                        'type' => 'string',
                        'accessor' => '$accessor',
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
                        'type' => 'string',
                        'accessor' => '$accessor',
                        'context' => [],
                    ];
                },
            ],
        ];

        $generator = self::createObjectTemplateGenerator();
        $generator->generate(new Type('object', className: ClassicDummy::class), new VariableNode('accessor'), $context, self::createTemplateGeneratorStub());

        $this->assertSame(2, $hookCallCount);
    }

    private static function createObjectTemplateGenerator(): ObjectTemplateGenerator
    {
        return new ObjectTemplateGenerator(
            'BEFORE_PROPERTIES',
            'AFTER_PROPERTIES',
            'PROPERTY_SEPARATOR',
            'BEFORE_PROPERTY_NAME',
            'AFTER_PROPERTY_NAME',
            fn (string $s) => sprintf('ESCAPE(%s)', $s),
        );
    }
}
