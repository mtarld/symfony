<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

use Symfony\Component\Marshaller\Native\Template\DictTemplateGenerator;
use Symfony\Component\Marshaller\Native\Template\TemplateGeneratorInterface;
use Symfony\Component\Marshaller\Native\Type\Type;

final class DictTemplateGeneratorTest extends TemplateGeneratorTestCase
{
    public function testGenerate(): void
    {
        $templateGenerator = $this->createMock(TemplateGeneratorInterface::class);
        $templateGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(new Type('int'), '$value_0', ['indentation_level' => 1, 'variable_counters' => ['prefix' => 1, 'key' => 1, 'value' => 1]])
            ->willReturn('NESTED'.PHP_EOL);

        $dictTemplateGenerator = new class ($templateGenerator) extends DictTemplateGenerator {
            protected function beforeValues(): string
            {
                return 'BEFORE_VALUES';
            }

            protected function afterValues(): string
            {
                return 'AFTER_VALUES';
            }

            protected function valueSeparator(): string
            {
                return 'VALUE_SEPARATOR';
            }

            protected function keyName(string $name): string
            {
                return "KEY($name)";
            }
        };

        $type = new Type('array', isGeneric: true, genericParameterTypes: [new Type('string'), new Type('int')]);
        $template = $dictTemplateGenerator->generate($type, '$accessor', $this->context());

        $this->assertSame([
            '\fwrite($resource, \'BEFORE_VALUES\');',
            '$prefix_0 = \'\';',
            'foreach ($accessor as $key_0 => $value_0) {',
            '    \fwrite($resource, $prefix_0.KEY($key_0));',
            'NESTED',
            '    $prefix_0 = \'VALUE_SEPARATOR\';',
            '}',
            '\fwrite($resource, \'AFTER_VALUES\');',
        ], $this->lines($template));
    }
}
