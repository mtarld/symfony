<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Hook;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Hook\ObjectHook;
use Symfony\Component\Marshaller\Tests\Fixtures\ClassicDummy;
use Symfony\Component\Marshaller\Type\TypeExtractorInterface;

final class ObjectHookTest extends TestCase
{
    /**
     * @dataProvider addGenericParameterTypesDataProvider
     *
     * @param array<class-string, array<string, string>> $expectedGenericParameterTypes
     * @param list<string>                               $templates
     */
    public function testAddGenericParameterTypes(array $expectedGenericParameterTypes, string $type, array $templates): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractTemplateFromClass')->willReturn($templates);

        $hookGeneratedContext = [];

        $context = [
            'type_template_generator' => static function (string $type, string $accessor, array $context) use (&$hookGeneratedContext): string {
                $hookGeneratedContext = $context;

                return 'TYPE_TEMPLATE';
            },
        ];

        $result = (new ObjectHook($typeExtractor))($type, '$accessor', 'format', $context);

        $this->assertSame('TYPE_TEMPLATE', $result);
        $this->assertSame($expectedGenericParameterTypes, $hookGeneratedContext['symfony']['generic_parameter_types'] ?? []);
    }

    /**
     * @return iterable<array{0: array<class-string, array<string, string>>, 1: string, 2: list<string>}>
     */
    public function addGenericParameterTypesDataProvider(): iterable
    {
        yield [[], 'int', []];
        yield [[], 'Foo<int>', ['T']];
        yield [[ClassicDummy::class => ['T' => 'int']], ClassicDummy::class.'<int>', ['T']];
        yield [[ClassicDummy::class => ['Tk' => 'int', 'Tv' => 'string']], ClassicDummy::class.'<int, string>', ['Tk', 'Tv']];
    }

    public function testThrowOnInvalidGenericString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "Foo<int, Bar<string>" type.');

        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        (new ObjectHook($typeExtractor))('Foo<int, Bar<string>', '$accessor', 'format', []);
    }

    public function testThrowOnWrongGenericTypeCount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Given 1 generic parameters in "%s<int>", but 2 templates are defined in "%1$s".', ClassicDummy::class));

        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractTemplateFromClass')->willReturn(['Tk', 'Tv']);

        (new ObjectHook($typeExtractor))(ClassicDummy::class.'<int>', '$accessor', 'format', []);
    }
}
