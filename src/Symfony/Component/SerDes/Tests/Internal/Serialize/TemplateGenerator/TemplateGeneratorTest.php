<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Internal\Serialize\TemplateGenerator;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Exception\CircularReferenceException;
use Symfony\Component\SerDes\Exception\LogicException;
use Symfony\Component\SerDes\Exception\UnsupportedTypeException;
use Symfony\Component\SerDes\Internal\Serialize\Compiler;
use Symfony\Component\SerDes\Internal\Serialize\Node\AssignNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\BinaryNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ExpressionNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\FunctionNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\IfNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\RawNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ScalarNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\VariableNode;
use Symfony\Component\SerDes\Internal\Serialize\NodeInterface;
use Symfony\Component\SerDes\Internal\Serialize\TemplateGenerator\TemplateGenerator;
use Symfony\Component\SerDes\Internal\Serialize\TypeSorter;
use Symfony\Component\SerDes\Internal\Type;
use Symfony\Component\SerDes\Internal\TypeFactory;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ConstructorPropertyPromotedDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithNotPublicProperty;
use Symfony\Component\SerDes\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\SerDes\Type\ReflectionTypeExtractor;

class TemplateGeneratorTest extends TestCase
{
    private readonly TemplateGenerator $templateGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templateGenerator = new DummyTemplateGenerator(
            new ReflectionTypeExtractor(),
            new TypeSorter(),
        );
    }

    public function testThrowOnInvalidType()
    {
        $this->expectException(UnsupportedTypeException::class);

        $this->templateGenerator->generate(TypeFactory::createFromString('foo'), new VariableNode('accessor'), []);
    }

    public function testGenerateNullable()
    {
        $this->assertEquals([
            'closures',
            new IfNode(
                new BinaryNode('===', new ScalarNode(null), new VariableNode('accessor')),
                ['null'],
                ['$accessor (?int)'],
            ),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('?int'), new VariableNode('accessor'), []));
    }

    public function testGenerateUnion()
    {
        $this->assertEquals([
            'closures',
            new IfNode(
                new FunctionNode('\is_int', [new VariableNode('accessor')]),
                ['$accessor (int)'],
                ['$accessor (string)'],
            ),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('int|string'), new VariableNode('accessor'), []));

        $this->assertEquals([
            'closures',
            new IfNode(
                new FunctionNode('\is_int', [new VariableNode('accessor')]),
                ['$accessor (int)'],
                ['$accessor (float)'],
                [[
                    'condition' => new FunctionNode('\is_string', [new VariableNode('accessor')]),
                    'body' => ['$accessor (string)'],
                ]]
            ),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('int|string|float'), new VariableNode('accessor'), []));
    }

    public function testGenerateNull()
    {
        $this->assertEquals(['closures', 'null'], $this->templateGenerator->generate(TypeFactory::createFromString('null'), new VariableNode('accessor'), []));
    }

    public function testGenerateScalar()
    {
        $this->assertEquals(['closures', '$accessor (int)'], $this->templateGenerator->generate(TypeFactory::createFromString('int'), new VariableNode('accessor'), []));
    }

    public function testGenerateList()
    {
        $this->assertEquals(['closures', '$accessor (list(array<int, int>))'], $this->templateGenerator->generate(TypeFactory::createFromString('array<int, int>'), new VariableNode('accessor'), []));
    }

    public function testGenerateDict()
    {
        $this->assertEquals(['closures', '$accessor (dict(array<string, int>))'], $this->templateGenerator->generate(TypeFactory::createFromString('array<string, int>'), new VariableNode('accessor'), []));
    }

    public function testGenerateEnum()
    {
        $this->assertEquals(['closures', sprintf('$accessor->value (%s)', DummyBackedEnum::class)], $this->templateGenerator->generate(TypeFactory::createFromString(DummyBackedEnum::class), new VariableNode('accessor'), []));
    }

    public function testGenerateObject()
    {
        $this->assertEquals([
            'closures',
            new ExpressionNode(new AssignNode(new VariableNode('object_0'), new VariableNode('accessor'))),
            '$object_0->id (id(int))',
            '$object_0->name (name(string))',
        ], $this->templateGenerator->generate(TypeFactory::createFromString(ClassicDummy::class), new VariableNode('accessor'), []));
    }

    public function testGenerateObjedctWithConstructorPropertyPromotion()
    {
        $this->assertEquals([
            'closures',
            new ExpressionNode(new AssignNode(new VariableNode('object_0'), new VariableNode('accessor'))),
            '$object_0->id (id(int))',
        ], $this->templateGenerator->generate(TypeFactory::createFromString(ConstructorPropertyPromotedDummy::class), new VariableNode('accessor'), []));
    }

    public function testGenerateObjectThrowWhenPropertyIsNotPublic()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('"%s::$name" must be public.', DummyWithNotPublicProperty::class));

        $this->templateGenerator->generate(TypeFactory::createFromString(DummyWithNotPublicProperty::class), new VariableNode('accessor'), []);
    }

    public function testGenerateObjectWithObjectHook()
    {
        $context = [
            'hooks' => [
                'serialize' => [
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
            'closures',
            new ExpressionNode(new AssignNode(new VariableNode('object_0'), new RawNode('$ACCESSOR'))),
            '$object_0->id (id(int))',
        ], $this->templateGenerator->generate(TypeFactory::createFromString(ClassicDummy::class), new VariableNode('accessor'), $context));
    }

    public function testGenerateObjectCallProperObjectHook()
    {
        $hookCallCount = 0;

        $context = [
            'custom_context_value' => true,
            'hooks' => [
                'serialize' => [
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

    public function testGenerateObjectWithPropertyHook()
    {
        $context = [
            'hooks' => [
                'serialize' => [
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
            'closures',
            new ExpressionNode(new AssignNode(new VariableNode('object_0'), new VariableNode('accessor'))),
            '$ACCESSOR (NAME(string))',
            '$ACCESSOR (NAME(string))',
        ], $this->templateGenerator->generate(TypeFactory::createFromString(ClassicDummy::class), new VariableNode('accessor'), $context));
    }

    public function testGenerateObjectCallProperPropertyHook()
    {
        $hookCallCount = 0;

        $context = [
            'custom_context_value' => true,
            'hooks' => [
                'serialize' => [
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

    public function testThrowOnCircularReference()
    {
        $this->templateGenerator->generate(TypeFactory::createFromString(ClassicDummy::class), new VariableNode('accessor'), []);
        $this->addToAssertionCount(1);

        $this->expectException(CircularReferenceException::class);

        $this->templateGenerator->generate(TypeFactory::createFromString(ClassicDummy::class), new VariableNode('accessor'), ['generated_classes' => [ClassicDummy::class => true]]);
    }
}

final class DummyTemplateGenerator extends TemplateGenerator
{
    protected function initialClosuresNodes(array $context): array
    {
        return ['closures'];
    }

    protected function nullNodes(array $context): array
    {
        return ['null'];
    }

    protected function scalarNodes(Type $type, NodeInterface $accessor, array $context): array
    {
        return [sprintf('%s (%s)', (new Compiler())->compile($accessor)->source(), (string) $type)];
    }

    protected function listNodes(Type $type, NodeInterface $accessor, array $context): array
    {
        return [sprintf('%s (list(%s))', (new Compiler())->compile($accessor)->source(), (string) $type)];
    }

    protected function dictNodes(Type $type, NodeInterface $accessor, array $context): array
    {
        return [sprintf('%s (dict(%s))', (new Compiler())->compile($accessor)->source(), (string) $type)];
    }

    protected function objectNodes(Type $type, array $propertiesInfo, array $context): array
    {
        return array_map(fn (array $i) => sprintf('%s (%s(%s))', (new Compiler())->compile($i['accessor'])->source(), $i['name'], $i['type']), $propertiesInfo);
    }
}
