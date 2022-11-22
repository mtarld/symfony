<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

use Symfony\Component\Marshaller\Native\Template\ObjectTemplateGenerator;
use Symfony\Component\Marshaller\Native\Template\TemplateGeneratorInterface;
use Symfony\Component\Marshaller\Native\Type\Type;
use Symfony\Component\Marshaller\Tests\Fixtures\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\DummyWithNotPublicProperty;

final class ObjectTemplateGeneratorTest extends TemplateGeneratorTestCase
{
    public function testGenerate(): void
    {
        $templateGenerator = $this->createMock(TemplateGeneratorInterface::class);
        $templateGenerator
            ->expects($this->exactly(2))
            ->method('generate')
            ->withConsecutive(
                [new Type('int'), '$object_0->id', ['indentation_level' => 0, 'variable_counters' => ['object' => 1]]],
                [new Type('string'), '$object_0->name', ['indentation_level' => 0, 'variable_counters' => ['object' => 1]]],
            )
            ->willReturn('NESTED'.PHP_EOL);

        $template = $this->createObjectGenerator($templateGenerator)->generate(new Type('object', className: ClassicDummy::class), '$accessor', $this->context());

        $this->assertSame([
            '$object_0 = $accessor;',
            '\fwrite($resource, \'BEFORE_PROPERTIES\');',
            '\fwrite($resource, \'BEFORE_PROPERTY_NAME\');',
            '\fwrite($resource, \'id\');',
            '\fwrite($resource, \'AFTER_PROPERTY_NAME\');',
            'NESTED',
            '\fwrite($resource, \'PROPERTY_SEPARATOR\');',
            '\fwrite($resource, \'BEFORE_PROPERTY_NAME\');',
            '\fwrite($resource, \'name\');',
            '\fwrite($resource, \'AFTER_PROPERTY_NAME\');',
            'NESTED',
            '\fwrite($resource, \'AFTER_PROPERTIES\');',
        ], $this->lines($template));
    }

    public function testThrowWhenPropertyIsNotPublic(): void
    {
        $templateGenerator = $this->createStub(TemplateGeneratorInterface::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('"%s::$name" must be public', DummyWithNotPublicProperty::class));

        $this->createObjectGenerator($templateGenerator)->generate(new Type('object', className: DummyWithNotPublicProperty::class), '$accessor', $this->context());
    }

    public function testReplaceContentWithPropertyHook(): void
    {
        $context = $this->context() + [
            'hooks' => [
                'property' => static function (\ReflectionProperty $property, string $accessor, string $format, array $context): ?string {
                    return 'PROPERTY_HOOK'.PHP_EOL;
                },
            ],
        ];

        $templateGenerator = $this->createStub(TemplateGeneratorInterface::class);
        $templateGenerator->method('format')->willReturn('FORMAT');

        $template = $this->createObjectGenerator($templateGenerator)->generate(new Type('object', className: ClassicDummy::class), '$accessor', $context);

        $this->assertSame([
            '$object_0 = $accessor;',
            '\fwrite($resource, \'BEFORE_PROPERTIES\');',
            'PROPERTY_HOOK',
            '\fwrite($resource, \'PROPERTY_SEPARATOR\');',
            'PROPERTY_HOOK',
            '\fwrite($resource, \'AFTER_PROPERTIES\');',
        ], $this->lines($template));
    }

    public function testDoNotReplaceContentWithPropertyHookNullResult(): void
    {
        $hookCallCount = 0;

        $context = $this->context() + [
            'hooks' => [
                'property' => static function (\ReflectionProperty $property, string $accessor, string $format, array $context) use (&$hookCallCount): ?string {
                    ++$hookCallCount;

                    return null;
                },
            ],
        ];

        $templateGenerator = $this->createStub(TemplateGeneratorInterface::class);
        $templateGenerator->method('generate')->willReturn('NESTED'.PHP_EOL);
        $templateGenerator->method('format')->willReturn('FORMAT');

        $template = $this->createObjectGenerator($templateGenerator)->generate(new Type('object', className: ClassicDummy::class), '$accessor', $context);

        $this->assertSame([
            '$object_0 = $accessor;',
            '\fwrite($resource, \'BEFORE_PROPERTIES\');',
            '\fwrite($resource, \'BEFORE_PROPERTY_NAME\');',
            '\fwrite($resource, \'id\');',
            '\fwrite($resource, \'AFTER_PROPERTY_NAME\');',
            'NESTED',
            '\fwrite($resource, \'PROPERTY_SEPARATOR\');',
            '\fwrite($resource, \'BEFORE_PROPERTY_NAME\');',
            '\fwrite($resource, \'name\');',
            '\fwrite($resource, \'AFTER_PROPERTY_NAME\');',
            'NESTED',
            '\fwrite($resource, \'AFTER_PROPERTIES\');',
        ], $this->lines($template));

        $this->assertSame(2, $hookCallCount);
    }

    public function testPropertyHookArguments(): void
    {
        $hookCallCount = 0;

        $context = $this->context() + [
            'hooks' => [
                sprintf('%s::$id', ClassicDummy::class) => function (\ReflectionProperty $property, string $accessor, string $format, array $context) use (&$hookCallCount): ?string {
                    ++$hookCallCount;

                    $this->assertEquals(new \ReflectionProperty(ClassicDummy::class, 'id'), $property);
                    $this->assertSame('$object_0->id', $accessor);
                    $this->assertSame('FORMAT', $format);
                    $this->assertCount(5, $context);

                    $this->assertSame(0, $context['indentation_level']);
                    $this->assertSame(['object' => 1], $context['variable_counters']);
                    $this->assertArrayHasKey('hooks', $context);
                    $this->assertIsCallable($context['property_name_template_generator']);
                    $this->assertIsCallable($context['property_value_template_generator']);

                    return 'ID_HOOK';
                },
                sprintf('%s::$name', ClassicDummy::class) => function (\ReflectionProperty $property, string $accessor, string $format, array $context) use (&$hookCallCount): ?string {
                    ++$hookCallCount;

                    $this->assertEquals(new \ReflectionProperty(ClassicDummy::class, 'name'), $property);
                    $this->assertSame('$object_0->name', $accessor);
                    $this->assertSame('FORMAT', $format);
                    $this->assertCount(5, $context);

                    return 'NAME_HOOK';
                },
            ],
        ];

        $templateGenerator = $this->createStub(TemplateGeneratorInterface::class);
        $templateGenerator->method('generate')->willReturn('NESTED'.PHP_EOL);
        $templateGenerator->method('format')->willReturn('FORMAT');

        $this->createObjectGenerator($templateGenerator)->generate(new Type('object', className: ClassicDummy::class), '$accessor', $context);

        $this->assertSame(2, $hookCallCount);
    }

    public function testPropertyNameGenerator(): void
    {
        $hookCallCount = 0;

        $context = $this->context() + [
            'hooks' => [
                sprintf('%s::$id', ClassicDummy::class) => function (\ReflectionProperty $property, string $accessor, string $format, array $context) use (&$hookCallCount): ?string {
                    ++$hookCallCount;

                    $this->assertSame([
                        '\fwrite($resource, \'BEFORE_PROPERTY_NAME\');',
                        '\fwrite($resource, \'ID_NAME\');',
                        '\fwrite($resource, \'AFTER_PROPERTY_NAME\');',
                    ], $this->lines($context['property_name_template_generator']("'ID_NAME'", $context)));

                    return 'ID_HOOK';
                },
            ],
        ];

        $templateGenerator = $this->createStub(TemplateGeneratorInterface::class);
        $templateGenerator->method('generate')->willReturn('NESTED'.PHP_EOL);
        $templateGenerator->method('format')->willReturn('FORMAT');

        $this->createObjectGenerator($templateGenerator)->generate(new Type('object', className: ClassicDummy::class), '$accessor', $context);

        $this->assertSame(1, $hookCallCount);
    }

    public function testPropertyValueGenerator(): void
    {
        $hookCallCount = 0;

        $context = $this->context() + [
            'hooks' => [
                sprintf('%s::$id', ClassicDummy::class) => function (\ReflectionProperty $property, string $accessor, string $format, array $context) use (&$hookCallCount): ?string {
                    ++$hookCallCount;

                    $this->assertSame(['NESTED'], $this->lines($context['property_value_template_generator']('int', '$object_0->id', $context)));

                    return 'ID_HOOK';
                },
            ],
        ];

        $templateGenerator = $this->createStub(TemplateGeneratorInterface::class);
        $templateGenerator->method('generate')->willReturn('NESTED'.PHP_EOL);
        $templateGenerator->method('format')->willReturn('FORMAT');

        $this->createObjectGenerator($templateGenerator)->generate(new Type('object', className: ClassicDummy::class), '$accessor', $context);

        $this->assertSame(1, $hookCallCount);
    }

    private function createObjectGenerator(TemplateGeneratorInterface $templateGenerator): ObjectTemplateGenerator
    {
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
        };
    }
}
