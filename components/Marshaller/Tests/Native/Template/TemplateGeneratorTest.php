<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

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

final class TemplateGeneratorTest extends TemplateGeneratorTestCase
{
    /**
     * @dataProvider generateValueTemplateDataProvider
     *
     * @param list<string> $expectedLines
     */
    public function testGenerateValueTemplate(array $expectedLines, Type|UnionType $type): void
    {
        $template = $this->createGenerator()->generate($type, '$accessor', $this->context());

        $this->assertSame($expectedLines, $this->lines($template));
    }

    /**
     * @return iterable<array{0: list<string>, 1: Type|UnionType}
     */
    public function generateValueTemplateDataProvider(): iterable
    {
        yield [[], new UnionType([])];
        yield [['NULL'], new Type('null')];
        yield [['SCALAR'], new Type('int')];
        yield [['OBJECT'], new Type('object', className: ClassicDummy::class)];
        yield [['LIST'], new Type('array', collectionKeyType: new Type('int'), collectionValueType: new Type('int'))];
        yield [['DICT'], new Type('array', collectionKeyType: new Type('string'), collectionValueType: new Type('int'))];
    }

    public function testThrowOnInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown "foo" type.');

        $this->createGenerator()->generate(new Type('foo'), '$accessor', $this->context());
    }

    public function testGenerateForNullable(): void
    {
        $template = $this->createGenerator()->generate(new Type('int', isNullable: true), '$accessor', $this->context());

        $this->assertSame([
            'if (null === $accessor) {',
            'NULL',
            '} else {',
            'SCALAR',
            '}',
        ], $this->lines($template));
    }

    public function testReplaceContentWithTypeHook(): void
    {
        $context = $this->context() + [
            'hooks' => [
                'type' => static function (string $type, string $accessor, string $format, array $context): ?string {
                    return 'TYPE_HOOK'.PHP_EOL;
                },
            ],
        ];

        $template = $this->createGenerator()->generate(new Type('int'), '$accessor', $context);
        $this->assertSame(['TYPE_HOOK'], $this->lines($template));

        $template = $this->createGenerator()->generate(new Type('int', isNullable: true), '$accessor', $context);
        $this->assertSame([
            'if (null === $accessor) {',
            'TYPE_HOOK',
            '} else {',
            'TYPE_HOOK',
            '}',
        ], $this->lines($template));
    }

    public function testDoNotReplaceContentWithTypeHookNullResult(): void
    {
        $hookCallCount = 0;

        $context = $this->context() + [
            'hooks' => [
                'type' => static function (string $type, string $accessor, string $format, array $context) use (&$hookCallCount): ?string {
                    ++$hookCallCount;

                    return null;
                },
            ],
        ];

        $template = $this->createGenerator()->generate(new Type('int', isNullable: true), '$accessor', $context);
        $this->assertSame([
            'if (null === $accessor) {',
            'NULL',
            '} else {',
            'SCALAR',
            '}',
        ], $this->lines($template));

        $this->assertSame(2, $hookCallCount);
    }

    public function testTypeHookArguments(): void
    {
        $hookCallCount = 0;

        $context = $this->context() + [
            'hooks' => [
                'int' => function (string $type, string $accessor, string $format, array $context) use (&$hookCallCount): ?string {
                    ++$hookCallCount;

                    $this->assertSame('?int', $type);
                    $this->assertSame('$accessor', $accessor);
                    $this->assertSame('FORMAT', $format);
                    $this->assertCount(3, $context);

                    $this->assertSame(1, $context['indentation_level']);
                    $this->assertArrayHasKey('hooks', $context);
                    $this->assertIsCallable($context['type_template_generator']);

                    return 'INT_HOOK'.PHP_EOL;
                },
                'null' => function (string $type, string $accessor, string $format, array $context) use (&$hookCallCount): ?string {
                    ++$hookCallCount;

                    $this->assertSame('null', $type);
                    $this->assertSame('NO_ACCESSOR', $accessor);
                    $this->assertSame('FORMAT', $format);
                    $this->assertCount(3, $context);

                    $this->assertSame(1, $context['indentation_level']);

                    return 'NULL_HOOK'.PHP_EOL;
                },
            ],
        ];

        $template = $this->createGenerator()->generate(new Type('int', isNullable: true), '$accessor', $context);
        $this->assertSame([
            'if (null === $accessor) {',
            'NULL_HOOK',
            '} else {',
            'INT_HOOK',
            '}',
        ], $this->lines($template));

        $this->assertSame(2, $hookCallCount);
    }

    public function testTypeGenerator(): void
    {
        $hookCallCount = 0;

        $context = $this->context() + [
            'hooks' => [
                'int' => function (string $type, string $accessor, string $format, array $context) use (&$hookCallCount): ?string {
                    ++$hookCallCount;

                    $this->assertSame(['DICT'], $this->lines($context['type_template_generator']('array<string, int>', $accessor, $context)));

                    return 'INT_HOOK'.PHP_EOL;
                },
            ],
        ];

        $template = $this->createGenerator()->generate(new Type('int'), '$accessor', $context);
        $this->assertSame(['INT_HOOK'], $this->lines($template));

        $this->assertSame(1, $hookCallCount);
    }

    public function testCheckForCircularReferences(): void
    {
        $context = $this->context();
        $generator = $this->createGenerator();

        $generator->generate(new Type('object', className: 'foo'), '$accessor', $context);
        $this->addToAssertionCount(1);

        $context['generated_classes']['foo'] = true;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Circular reference detected on "foo" detected.');

        $generator->generate(new Type('object', className: 'foo'), '$accessor', $context);
    }

    private function createGenerator(): TemplateGenerator
    {
        $scalarGenerator = $this->createStub(ScalarTemplateGenerator::class);
        $scalarGenerator->method('generate')->willReturn('SCALAR'.PHP_EOL);

        $nullGenerator = $this->createStub(NullTemplateGenerator::class);
        $nullGenerator->method('generate')->willReturn('NULL'.PHP_EOL);

        $objectGenerator = $this->createStub(ObjectTemplateGenerator::class);
        $objectGenerator->method('generate')->willReturn('OBJECT'.PHP_EOL);

        $listGenerator = $this->createStub(ListTemplateGenerator::class);
        $listGenerator->method('generate')->willReturn('LIST'.PHP_EOL);

        $dictGenerator = $this->createStub(DictTemplateGenerator::class);
        $dictGenerator->method('generate')->willReturn('DICT'.PHP_EOL);

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
