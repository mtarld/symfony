<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template\Json;

use Symfony\Component\Marshaller\Native\Template\Json\JsonNullTemplateGenerator;
use Symfony\Component\Marshaller\Tests\Native\Template\TemplateGeneratorTestCase;

final class JsonNullGeneratorTest extends TemplateGeneratorTestCase
{
    public function testGenerate(): void
    {
        $template = (new JsonNullTemplateGenerator())->generate($this->context());

        $this->assertSame([
            '\fwrite($resource, \'null\');',
        ], $this->lines($template));
    }
}
