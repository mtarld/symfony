<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template\Json;

use Symfony\Component\Marshaller\Native\Template\Json\JsonScalarTemplateGenerator;
use Symfony\Component\Marshaller\Native\Type\Type;
use Symfony\Component\Marshaller\Tests\Native\Template\TemplateGeneratorTestCase;

final class JsonScalarTemplateGeneratorTest extends TemplateGeneratorTestCase
{
    public function testGenerate(): void
    {
        $template = (new JsonScalarTemplateGenerator())->generate(new Type('int'), '$accessor', $this->context());

        $this->assertSame([
            '\fwrite($resource, $accessor);',
        ], $this->lines($template));
    }

    public function testGenerateString(): void
    {
        $template = (new JsonScalarTemplateGenerator())->generate(new Type('string'), '$accessor', $this->context());

        $this->assertSame([
            '\fwrite($resource, \'"\');',
            '\fwrite($resource, addcslashes($accessor, "\0\t\"\$\\\"));',
            '\fwrite($resource, \'"\');',
        ], $this->lines($template));
    }
}
