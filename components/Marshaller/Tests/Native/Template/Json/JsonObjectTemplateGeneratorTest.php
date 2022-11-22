<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template\Json;

use Symfony\Component\Marshaller\Native\Template\Json\JsonObjectTemplateGenerator;
use Symfony\Component\Marshaller\Native\Template\TemplateGeneratorInterface;
use Symfony\Component\Marshaller\Native\Type\Type;
use Symfony\Component\Marshaller\Tests\Fixtures\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Native\Template\TemplateGeneratorTestCase;

final class JsonObjectTemplateGeneratorTest extends TemplateGeneratorTestCase
{
    public function testGenerate(): void
    {
        $templateGenerator = $this->createStub(TemplateGeneratorInterface::class);
        $templateGenerator->method('generate')->willReturn('NESTED'.PHP_EOL);

        $type = new Type('object', className: ClassicDummy::class);
        $template = (new JsonObjectTemplateGenerator($templateGenerator))->generate($type, '$accessor', $this->context());

        $this->assertSame([
            '$object_0 = $accessor;',
            '\fwrite($resource, \'{\');',
            '\fwrite($resource, \'"\');',
            '\fwrite($resource, \'id\');',
            '\fwrite($resource, \'":\');',
            'NESTED',
            '\fwrite($resource, \',\');',
            '\fwrite($resource, \'"\');',
            '\fwrite($resource, \'name\');',
            '\fwrite($resource, \'":\');',
            'NESTED',
            '\fwrite($resource, \'}\');',
        ], $this->lines($template));
    }
}
