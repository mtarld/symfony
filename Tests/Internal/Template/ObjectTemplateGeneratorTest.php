<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Internal\Template;

use Symfony\Component\Marshaller\Internal\Ast\Node\AssignNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\PropertyNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\RawNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Internal\Template\ObjectTemplateGenerator;
use Symfony\Component\Marshaller\Internal\Type\Type;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ConstructorPropertyPromotedDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithNotPublicProperty;

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
