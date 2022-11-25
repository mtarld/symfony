<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template\Json;

use Symfony\Component\Marshaller\Native\Template\Json\JsonDictTemplateGenerator;
use Symfony\Component\Marshaller\Native\Template\TemplateGeneratorInterface;
use Symfony\Component\Marshaller\Native\Type\Type;
use Symfony\Component\Marshaller\Tests\Native\Template\TemplateGeneratorTestCase;

final class JsonDictTemplateGeneratorTest extends TemplateGeneratorTestCase
{
    public function testGenerate(): void
    {
        $templateGenerator = $this->createStub(TemplateGeneratorInterface::class);
        $templateGenerator->method('generate')->willReturn('NESTED'.PHP_EOL);

        $type = new Type('array', isGeneric: true, genericParameterTypes: [new Type('string'), new Type('int')]);
        $template = (new JsonDictTemplateGenerator($templateGenerator))->generate($type, '$accessor', $this->context());

        $this->assertSame([
            '\fwrite($resource, \'{\');',
            '$prefix_0 = \'\';',
            'foreach ($accessor as $key_0 => $value_0) {',
            '    \fwrite($resource, $prefix_0.\json_encode($key_0).\':\');',
            'NESTED',
            '    $prefix_0 = \',\';',
            '}',
            '\fwrite($resource, \'}\');',
        ], $this->lines($template));
    }
}
