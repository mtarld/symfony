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
use Symfony\Component\SerDes\Internal\Serialize\Compiler;
use Symfony\Component\SerDes\Internal\Serialize\Node\AssignNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\BinaryNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ExpressionNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\FunctionNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\IfNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ScalarNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\VariableNode;
use Symfony\Component\SerDes\Internal\Serialize\NodeInterface;
use Symfony\Component\SerDes\Internal\Serialize\TemplateGenerator\TemplateGenerator;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ConstructorPropertyPromotedDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\DummyWithNotPublicProperty;
use Symfony\Component\SerDes\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\SerDes\Type\ReflectionTypeExtractor;
use Symfony\Component\SerDes\Type\Type;
use Symfony\Component\SerDes\Type\TypeFactory;
use Symfony\Component\SerDes\Type\TypeSorter;

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

    public function testGenerateNullable()
    {
        $this->assertEquals([
            new IfNode(
                new BinaryNode('===', new ScalarNode(null), new VariableNode('accessor')),
                ['null'],
                ['$accessor scalar(?int)'],
            ),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('?int'), new VariableNode('accessor'), []));
    }

    public function testGenerateUnion()
    {
        $this->assertEquals([
            new IfNode(
                new FunctionNode('\is_int', [new VariableNode('accessor')]),
                ['$accessor scalar(int)'],
                ['$accessor scalar(string)'],
            ),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('int|string'), new VariableNode('accessor'), []));

        $this->assertEquals([
            new IfNode(
                new FunctionNode('\is_int', [new VariableNode('accessor')]),
                ['$accessor scalar(int)'],
                ['$accessor scalar(float)'],
                [[
                    'condition' => new FunctionNode('\is_string', [new VariableNode('accessor')]),
                    'body' => ['$accessor scalar(string)'],
                ]]
            ),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('int|string|float'), new VariableNode('accessor'), []));
    }

    public function testGenerateNull()
    {
        $this->assertEquals(['null'], $this->templateGenerator->generate(TypeFactory::createFromString('null'), new VariableNode('accessor'), []));
    }

    public function testGenerateScalar()
    {
        $this->assertEquals(['$accessor scalar(int)'], $this->templateGenerator->generate(TypeFactory::createFromString('int'), new VariableNode('accessor'), []));
    }

    public function testGenerateList()
    {
        $this->assertEquals(['$accessor (list(array<int, int>))'], $this->templateGenerator->generate(TypeFactory::createFromString('array<int, int>'), new VariableNode('accessor'), []));
    }

    public function testGenerateDict()
    {
        $this->assertEquals(['$accessor (dict(array<string, int>))'], $this->templateGenerator->generate(TypeFactory::createFromString('array<string, int>'), new VariableNode('accessor'), []));
    }

    public function testGenerateEnum()
    {
        $this->assertEquals([sprintf('$accessor->value scalar(%s)', DummyBackedEnum::class)], $this->templateGenerator->generate(TypeFactory::createFromString(DummyBackedEnum::class), new VariableNode('accessor'), []));
    }

    public function testGenerateObject()
    {
        $this->assertEquals([
            new ExpressionNode(new AssignNode(new VariableNode('object_0'), new VariableNode('accessor'))),
            '$object_0->id (id(int))',
            '$object_0->name (name(string))',
        ], $this->templateGenerator->generate(TypeFactory::createFromString(ClassicDummy::class), new VariableNode('accessor'), []));
    }

    public function testGenerateObjedctWithConstructorPropertyPromotion()
    {
        $this->assertEquals([
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

    public function testGenerateObjectCallProperObjectHook()
    {
        $hookCallCount = 0;

        $context = [
            'custom_context_value' => true,
            'hooks' => [
                'serialize' => [
                    ClassicDummy::class => function (string $type, string $accessor, array $properties, array $context) use (&$hookCallCount): array {
                        ++$hookCallCount;

                        $this->assertSame(ClassicDummy::class, $type);
                        $this->assertSame('$object_0', $accessor);
                        $this->assertEquals([
                            'id' => [
                                'name' => 'id',
                                'accessor' => '$object_0->id',
                                'type' => TypeFactory::createFromString('int'),
                            ],
                            'name' => [
                                'name' => 'name',
                                'accessor' => '$object_0->name',
                                'type' => TypeFactory::createFromString('string'),
                            ],
                        ], $properties);
                        $this->assertArrayHasKey('custom_context_value', $context);

                        return ['properties' => $properties, 'context' => $context];
                    },
                    ConstructorPropertyPromotedDummy::class => function (string $type, string $accessor, array $properties, array $context) use (&$hookCallCount): array {
                        ++$hookCallCount;

                        $this->assertSame(ConstructorPropertyPromotedDummy::class, $type);
                        $this->assertSame('$object_0', $accessor);
                        $this->assertEquals([
                            'id' => [
                                'name' => 'id',
                                'accessor' => '$object_0->id',
                                'type' => TypeFactory::createFromString('int'),
                            ],
                        ], $properties);
                        $this->assertArrayHasKey('custom_context_value', $context);

                        return ['properties' => $properties, 'context' => $context];
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

    public function testGenerateObjectWithHookUpdateProperties()
    {
        $context = [
            'hooks' => [
                'serialize' => [
                    ClassicDummy::class => function (string $type, string $accessor, array $properties, array $context): array {
                        $properties['id']['name'] = 'ID_NAME';
                        $properties['id']['accessor'] = '$ID_ACCESSOR';
                        $properties['id']['type'] = 'string';

                        $properties['name']['name'] = 'NAME_NAME';
                        $properties['name']['accessor'] = '$NAME_ACCESSOR';

                        return ['properties' => $properties];
                    },
                ],
            ],
        ];

        $this->assertEquals([
            new ExpressionNode(new AssignNode(new VariableNode('object_0'), new VariableNode('accessor'))),
            '$ID_ACCESSOR (ID_NAME(string))',
            '$NAME_ACCESSOR (NAME_NAME(string))',
        ], $this->templateGenerator->generate(TypeFactory::createFromString(ClassicDummy::class), new VariableNode('accessor'), $context));
    }

    public function testGenerateObjectSkipRemovedProperties()
    {
        $context = [
            'hooks' => [
                'serialize' => [
                    ClassicDummy::class => function (string $type, string $accessor, array $properties, array $context): array {
                        unset($properties['id']);

                        return ['properties' => $properties];
                    },
                ],
            ],
        ];

        $this->assertEquals([
            new ExpressionNode(new AssignNode(new VariableNode('object_0'), new VariableNode('accessor'))),
            '$object_0->name (name(string))',
        ], $this->templateGenerator->generate(TypeFactory::createFromString(ClassicDummy::class), new VariableNode('accessor'), $context));
    }

    public function testGenerateObjectAddAdditionalProperties()
    {
        $context = [
            'hooks' => [
                'serialize' => [
                    ClassicDummy::class => function (string $type, string $accessor, array $properties, array $context): array {
                        $properties['foo'] = [
                            'name' => 'NAME_FOO',
                            'type' => 'int',
                            'accessor' => sprintf('FOO_ACCESSOR(%s)', $accessor),
                        ];

                        return ['properties' => $properties];
                    },
                ],
            ],
        ];

        $this->assertEquals([
            new ExpressionNode(new AssignNode(new VariableNode('object_0'), new VariableNode('accessor'))),
            '$object_0->id (id(int))',
            '$object_0->name (name(string))',
            'FOO_ACCESSOR($object_0) (NAME_FOO(int))',
        ], $this->templateGenerator->generate(TypeFactory::createFromString(ClassicDummy::class), new VariableNode('accessor'), $context));
    }

    public function testGenerateMixed()
    {
        $this->assertEquals(['$accessor mixed'], $this->templateGenerator->generate(TypeFactory::createFromString('mixed'), new VariableNode('accessor'), []));
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
    protected function nullNodes(array $context): array
    {
        return ['null'];
    }

    protected function scalarNodes(Type $type, NodeInterface $accessor, array $context): array
    {
        return [sprintf('%s scalar(%s)', (new Compiler())->compile($accessor)->source(), (string) $type)];
    }

    protected function listNodes(Type $type, NodeInterface $accessor, array $context): array
    {
        return [sprintf('%s (list(%s))', (new Compiler())->compile($accessor)->source(), (string) $type)];
    }

    protected function dictNodes(Type $type, NodeInterface $accessor, array $context): array
    {
        return [sprintf('%s (dict(%s))', (new Compiler())->compile($accessor)->source(), (string) $type)];
    }

    protected function objectNodes(Type $type, array $properties, array $context): array
    {
        return array_values(array_map(fn (array $i) => sprintf('%s (%s(%s))', (new Compiler())->compile($i['accessor'])->source(), $i['name'], $i['type']), $properties));
    }

    protected function mixedNodes(NodeInterface $accessor, array $context): array
    {
        return [sprintf('%s mixed', (new Compiler())->compile($accessor)->source())];
    }
}
