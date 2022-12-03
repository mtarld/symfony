<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

use Symfony\Component\Marshaller\Native\Ast\Node\BinaryNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\IfNode;
use Symfony\Component\Marshaller\Native\Ast\Node\RawNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Native\Type\Type;
use Symfony\Component\Marshaller\Tests\Fixtures\ClassicDummy;

final class TemplateGeneratorTest extends TemplateGeneratorTestCase
{
    public function testThrowOnInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown "foo" type.');

        self::createTemplateGeneratorStub()->generate(new Type('foo'), new VariableNode('accessor'), []);
    }

    public function testGenerateForNullable(): void
    {
        $nodes = self::createTemplateGeneratorStub()->generate(new Type('int', isNullable: true), new VariableNode('accessor'), []);

        $this->assertEquals([
            new IfNode(
                new BinaryNode('===', new ScalarNode(null), new VariableNode('accessor')),
                [new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode(null)]))],
                [new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new VariableNode('accessor')]))],
            ),
        ], $nodes);
    }

    /**
     * @dataProvider throwWhenInvalidHookResultDataProvider
     *
     * @param array{type?: mixed, accessor?: mixed, context?: mixed} $hookResult
     */
    public function testThrowWhenInvalidHookResult(string $expectedExceptionMessage, array $hookResult): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        self::createTemplateGeneratorStub()->generate(new Type('int'), new VariableNode('accessor'), [
            'hooks' => [
                'type' => static function (string $type, string $accessor, array $context) use ($hookResult): array {
                    return $hookResult;
                },
            ],
        ]);
    }

    /**
     * @return iterable<array{0: string, 1: array{type?: mixed, accessor?: mixed, context?: mixed}}>
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

        $generator = self::createTemplateGeneratorStub();

        $nodes = $generator->generate(new Type('object', className: ClassicDummy::class), new VariableNode('accessor'), $context);
        $this->assertEquals([new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new RawNode('$ACCESSOR')]))], $nodes);

        $nodes = $generator->generate(new Type('object', className: ClassicDummy::class, isNullable: true), new VariableNode('accessor'), $context);
        $this->assertEquals([
            new IfNode(
                new BinaryNode('===', new ScalarNode(null), new VariableNode('accessor')),
                [new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new RawNode('$ACCESSOR')]))],
                [new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new RawNode('$ACCESSOR')]))],
            ),
        ], $nodes);
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

        self::createTemplateGeneratorStub()->generate(new Type('int', isNullable: true), new VariableNode('accessor'), $context);

        $this->assertSame(2, $hookCallCount);
    }

    public function testCheckForCircularReferences(): void
    {
        $generator = self::createTemplateGeneratorStub();

        $generator->generate(new Type('object', className: ClassicDummy::class), new VariableNode('accessor'), []);
        $this->addToAssertionCount(1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Circular reference detected on "%s" detected.', ClassicDummy::class));

        $generator->generate(new Type('object', className: ClassicDummy::class), new VariableNode('accessor'), ['generated_classes' => [ClassicDummy::class => true]]);
    }
}
