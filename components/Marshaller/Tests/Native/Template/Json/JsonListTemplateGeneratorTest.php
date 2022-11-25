<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template\Json;

use Symfony\Component\Marshaller\Native\Template\Json\JsonListTemplateGenerator;
use Symfony\Component\Marshaller\Native\Template\TemplateGeneratorInterface;
use Symfony\Component\Marshaller\Native\Type\Type;
use Symfony\Component\Marshaller\Tests\Native\Template\TemplateGeneratorTestCase;

final class JsonListTemplateGeneratorTest extends TemplateGeneratorTestCase
{
    public function testGenerate(): void
    {
        $templateGenerator = $this->createStub(TemplateGeneratorInterface::class);
        $templateGenerator->method('generate')->willReturn('NESTED'.PHP_EOL);

        $type = new Type('array', isGeneric: true, genericParameterTypes: [new Type('int'), new Type('int')]);
        $template = (new JsonListTemplateGenerator($templateGenerator))->generate($type, '$accessor', $this->context());

        $this->assertSame([
            '\fwrite($resource, \'[\');',
            '$prefix_0 = \'\';',
            'foreach ($accessor as $value_0) {',
            '    \fwrite($resource, $prefix_0);',
            'NESTED',
            '    $prefix_0 = \',\';',
            '}',
            '\fwrite($resource, \']\');',
        ], $this->lines($template));
    }
}
