<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

use Symfony\Component\Marshaller\Native\Template\NullTemplateGenerator;

final class NullTemplateGeneratorTest extends TemplateGeneratorTestCase
{
    public function testGenerate(): void
    {
        $nullTemplateGenerator = new class () extends NullTemplateGenerator {
            protected function null(array $context): string
            {
                return 'NULL'.PHP_EOL;
            }
        };

        $template = $nullTemplateGenerator->generate($this->context());

        $this->assertSame([
            'NULL',
        ], $this->lines($template));
    }
}
