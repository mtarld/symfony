<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Marshal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Exception\CircularReferenceException;
use Symfony\Component\Marshaller\Exception\LogicException;
use Symfony\Component\Marshaller\Exception\UnsupportedTypeException;
use Symfony\Component\Marshaller\Internal\Marshal\Node\AssignNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\BinaryNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ExpressionNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ForEachNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\FunctionNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\IfNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\PropertyNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\RawNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ScalarNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\TemplateStringNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\VariableNode;
use Symfony\Component\Marshaller\Internal\Marshal\NodeInterface;
use Symfony\Component\Marshaller\Internal\Marshal\SyntaxInterface;
use Symfony\Component\Marshaller\Internal\Marshal\TemplateGenerator;
use Symfony\Component\Marshaller\Internal\Marshal\TypeSorter;
use Symfony\Component\Marshaller\Internal\TypeFactory;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ConstructorPropertyPromotedDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithNotPublicProperty;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;

final class TemplateGeneratorTest extends TestCase
{
    private readonly TemplateGenerator $templateGenerator;

    public function __construct()
    {
        parent::__construct();

        $syntax = $this->createMock(SyntaxInterface::class);
        $syntax->method('startListString')->willReturn('START_LIST');
        $syntax->method('endListString')->willReturn('END_LIST');
        $syntax->method('startDictString')->willReturn('START_DICT');
        $syntax->method('endDictString')->willReturn('END_DICT');
        $syntax->method('startDictKeyString')->willReturn('START_DICT_KEY_STRING');
        $syntax->method('endDictKeyString')->willReturn('END_DICT_KEY_STRING');
        $syntax->method('collectionItemSeparatorString')->willReturn('COLLECTION_ITEM_SEPARATOR');
        $syntax->method('escapeString')->willReturnCallback(fn (string $s) => sprintf('ESCAPE(%s)', $s));
        $syntax->method('escapeStringNode')->willReturnCallback(fn (NodeInterface $n) => new FunctionNode('ESCAPE', [$n]));
        $syntax->method('encodeValueNode')->willReturnCallback(fn (NodeInterface $n) => new FunctionNode('ENCODE', [$n]));

        $this->templateGenerator = new TemplateGenerator(
            new ReflectionTypeExtractor(),
            new TypeSorter(),
            $syntax,
        );
    }

    public function testThrowOnInvalidType(): void
    {
        $this->expectException(UnsupportedTypeException::class);

        $this->templateGenerator->generate(TypeFactory::createFromString('foo'), new VariableNode('accessor'), []);
    }

    public function testGenerateNullable(): void
    {
        $this->assertEquals([
            new IfNode(
                new BinaryNode('===', new ScalarNode(null), new VariableNode('accessor')),
                [new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('ENCODE', [new VariableNode('accessor')])]))],
                [new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('ENCODE', [new VariableNode('accessor')])]))],
            ),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('?int'), new VariableNode('accessor'), []));
    }

    public function testGenerateUnion(): void
    {
        $this->assertEquals([
            new IfNode(
                new FunctionNode('\is_int', [new VariableNode('accessor')]),
                [new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('ENCODE', [new VariableNode('accessor')])]))],
                [new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('ENCODE', [new VariableNode('accessor')])]))],
            ),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('int|string'), new VariableNode('accessor'), []));

        $this->assertEquals([
            new IfNode(
                new FunctionNode('\is_int', [new VariableNode('accessor')]),
                [new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('ENCODE', [new VariableNode('accessor')])]))],
                [new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('ENCODE', [new VariableNode('accessor')])]))],
                [[
                    'condition' => new FunctionNode('\is_string', [new VariableNode('accessor')]),
                    'body' => [new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('ENCODE', [new VariableNode('accessor')])]))],
                ]]
            ),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('int|string|float'), new VariableNode('accessor'), []));
    }

    public function testGenerateNull(): void
    {
        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('ENCODE', [new VariableNode('accessor')])])),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('null'), new VariableNode('accessor'), []));
    }

    public function testGenerateScalar(): void
    {
        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('ENCODE', [new VariableNode('accessor')])])),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('int'), new VariableNode('accessor'), []));
    }

    public function testGenerateList(): void
    {
        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('START_LIST')])),
            new ExpressionNode(new AssignNode(new VariableNode('prefix_0'), new ScalarNode(''))),
            new ForEachNode(new VariableNode('accessor'), null, 'value_0', [
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new VariableNode('prefix_0')])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('ENCODE', [new VariableNode('value_0')])])),
                new ExpressionNode(new AssignNode(new VariableNode('prefix_0'), new ScalarNode('COLLECTION_ITEM_SEPARATOR'))),
            ]),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('END_LIST')])),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('array<int, int>'), new VariableNode('accessor'), []));
    }

    public function testGenerateDict(): void
    {
        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('START_DICT')])),
            new ExpressionNode(new AssignNode(new VariableNode('prefix_0'), new ScalarNode(''))),
            new ForEachNode(new VariableNode('accessor'), 'key_0', 'value_0', [
                new ExpressionNode(new AssignNode(new VariableNode('key_0'), new FunctionNode('ESCAPE', [new VariableNode('key_0')]))),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new TemplateStringNode(
                    new VariableNode('prefix_0'),
                    'START_DICT_KEY_STRING',
                    new VariableNode('key_0'),
                    'END_DICT_KEY_STRING',
                )])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('ENCODE', [new VariableNode('value_0')])])),
                new ExpressionNode(new AssignNode(new VariableNode('prefix_0'), new ScalarNode('COLLECTION_ITEM_SEPARATOR'))),
            ]),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('END_DICT')])),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('array<string, int>'), new VariableNode('accessor'), []));
    }

    public function testGenerateObject(): void
    {
        $this->assertEquals([
            new ExpressionNode(new AssignNode(new VariableNode('object_0'), new VariableNode('accessor'))),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('START_DICT')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('START_DICT_KEY_STRING')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('ESCAPE(id)')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('END_DICT_KEY_STRING')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('ENCODE', [new PropertyNode(new VariableNode('object_0'), 'id')])])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('COLLECTION_ITEM_SEPARATOR')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('START_DICT_KEY_STRING')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('ESCAPE(name)')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('END_DICT_KEY_STRING')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('ENCODE', [new PropertyNode(new VariableNode('object_0'), 'name')])])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('END_DICT')])),
        ], $this->templateGenerator->generate(TypeFactory::createFromString(ClassicDummy::class), new VariableNode('accessor'), []));
    }

    public function testGenerateObjedctWithConstructorPropertyPromotion(): void
    {
        $this->assertEquals([
            new ExpressionNode(new AssignNode(new VariableNode('object_0'), new VariableNode('accessor'))),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('START_DICT')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('START_DICT_KEY_STRING')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('ESCAPE(id)')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('END_DICT_KEY_STRING')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('ENCODE', [new PropertyNode(new VariableNode('object_0'), 'id')])])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('END_DICT')])),
        ], $this->templateGenerator->generate(TypeFactory::createFromString(ConstructorPropertyPromotedDummy::class), new VariableNode('accessor'), []));
    }

    public function testGenerateObjectThrowWhenPropertyIsNotPublic(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('"%s::$name" must be public.', DummyWithNotPublicProperty::class));

        $this->templateGenerator->generate(TypeFactory::createFromString(DummyWithNotPublicProperty::class), new VariableNode('accessor'), []);
    }

    public function testGenerateObjectWithObjectHook(): void
    {
        $context = [
            'hooks' => [
                'marshal' => [
                    'object' => static function (string $type, string $accessor, array $context): array {
                        return [
                            'type' => ConstructorPropertyPromotedDummy::class,
                            'accessor' => '$ACCESSOR',
                            'context' => ['CONTEXT'],
                        ];
                    },
                ],
            ],
        ];

        $this->assertEquals([
            new ExpressionNode(new AssignNode(new VariableNode('object_0'), new RawNode('$ACCESSOR'))),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('START_DICT')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('START_DICT_KEY_STRING')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('ESCAPE(id)')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('END_DICT_KEY_STRING')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('ENCODE', [new PropertyNode(new VariableNode('object_0'), 'id')])])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('END_DICT')])),
        ], $this->templateGenerator->generate(TypeFactory::createFromString(ClassicDummy::class), new VariableNode('accessor'), $context));
    }

    public function testGenerateObjectCallProperObjectHook(): void
    {
        $hookCallCount = 0;

        $context = [
            'custom_context_value' => true,
            'hooks' => [
                'marshal' => [
                    ClassicDummy::class => function (string $type, string $accessor, array $context) use (&$hookCallCount): array {
                        ++$hookCallCount;

                        $this->assertSame(ClassicDummy::class, $type);
                        $this->assertSame('$accessor', $accessor);
                        $this->assertArrayHasKey('custom_context_value', $context);

                        return ['type' => $type, 'accessor' => $accessor, 'context' => $context];
                    },
                    ConstructorPropertyPromotedDummy::class => function (string $type, string $accessor, array $context) use (&$hookCallCount): array {
                        ++$hookCallCount;

                        $this->assertSame(ConstructorPropertyPromotedDummy::class, $type);
                        $this->assertSame('$accessor', $accessor);
                        $this->assertArrayHasKey('custom_context_value', $context);

                        return ['type' => $type, 'accessor' => $accessor, 'context' => $context];
                    },
                ],
            ],
        ];

        $this->templateGenerator->generate(
            TypeFactory::createFromString(sprintf('%s|%s', ClassicDummy::class, ConstructorPropertyPromotedDummy::class)),
            new VariableNode('accessor'),
            $context,
        );

        $this->assertSame(2, $hookCallCount);
    }

    public function testGenerateObjectWithPropertyHook(): void
    {
        $context = [
            'hooks' => [
                'marshal' => [
                    'property' => static function (\ReflectionProperty $property, string $accessor, array $context): array {
                        return [
                            'name' => 'NAME',
                            'type' => 'string',
                            'accessor' => '$ACCESSOR',
                            'context' => ['CONTEXT'],
                        ];
                    },
                ],
            ],
        ];

        $this->assertEquals([
            new ExpressionNode(new AssignNode(new VariableNode('object_0'), new VariableNode('accessor'))),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('START_DICT')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('START_DICT_KEY_STRING')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('ESCAPE(NAME)')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('END_DICT_KEY_STRING')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('ENCODE', [new RawNode('$ACCESSOR')])])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('COLLECTION_ITEM_SEPARATOR')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('START_DICT_KEY_STRING')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('ESCAPE(NAME)')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('END_DICT_KEY_STRING')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('ENCODE', [new RawNode('$ACCESSOR')])])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('END_DICT')])),
        ], $this->templateGenerator->generate(TypeFactory::createFromString(ClassicDummy::class), new VariableNode('accessor'), $context));
    }

    public function testGenerateObjectCallProperPropertyHook(): void
    {
        $hookCallCount = 0;

        $context = [
            'custom_context_value' => true,
            'hooks' => [
                'marshal' => [
                    sprintf('%s::$id', ClassicDummy::class) => function (\ReflectionProperty $property, string $accessor, array $context) use (&$hookCallCount): array {
                        ++$hookCallCount;

                        $this->assertEquals(new \ReflectionProperty(ClassicDummy::class, 'id'), $property);
                        $this->assertSame('$object_0->id', $accessor);
                        $this->assertArrayHasKey('custom_context_value', $context);

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
                        $this->assertArrayHasKey('custom_context_value', $context);

                        return [
                            'name' => 'name',
                            'type' => 'string',
                            'accessor' => '$accessor',
                            'context' => [],
                        ];
                    },
                ],
            ],
        ];

        $this->templateGenerator->generate(TypeFactory::createFromString(ClassicDummy::class), new VariableNode('accessor'), $context);

        $this->assertSame(2, $hookCallCount);
    }

    public function testThrowOnCircularReference(): void
    {
        $this->templateGenerator->generate(TypeFactory::createFromString(ClassicDummy::class), new VariableNode('accessor'), []);
        $this->addToAssertionCount(1);

        $this->expectException(CircularReferenceException::class);

        $this->templateGenerator->generate(TypeFactory::createFromString(ClassicDummy::class), new VariableNode('accessor'), ['generated_classes' => [ClassicDummy::class => true]]);
    }
}
