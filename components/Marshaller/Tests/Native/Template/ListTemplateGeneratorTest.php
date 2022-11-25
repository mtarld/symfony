<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

use Symfony\Component\Marshaller\Native\Template\ListTemplateGenerator;
use Symfony\Component\Marshaller\Native\Template\TemplateGeneratorInterface;
use Symfony\Component\Marshaller\Native\Type\Type;

final class ListTemplateGeneratorTest extends TemplateGeneratorTestCase
{
    public function testGenerate(): void
    {
        $templateGenerator = $this->createMock(TemplateGeneratorInterface::class);
        $templateGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(new Type('int'), '$value_0', ['indentation_level' => 1, 'variable_counters' => ['prefix' => 1, 'value' => 1]])
            ->willReturn('NESTED'.PHP_EOL);

        $listTemplateGenerator = new class ($templateGenerator) extends ListTemplateGenerator {
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
        };

        $type = new Type('array', isGeneric: true, genericTypes:[new Type('int'), new Type('int')]);
        $template = $listTemplateGenerator->generate($type, '$accessor', $this->context());

        $this->assertSame([
            '\fwrite($resource, \'BEFORE_VALUES\');',
            '$prefix_0 = \'\';',
            'foreach ($accessor as $value_0) {',
            '    \fwrite($resource, $prefix_0);',
            'NESTED',
            '    $prefix_0 = \'VALUE_SEPARATOR\';',
            '}',
            '\fwrite($resource, \'AFTER_VALUES\');',
        ], $this->lines($template));
    }
}
