<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

use Symfony\Component\Marshaller\Native\Template\ScalarTemplateGenerator;
use Symfony\Component\Marshaller\Native\Type\Type;

final class ScalarTemplateGeneratorTest extends TemplateGeneratorTestCase
{
    public function testGenerate(): void
    {
        $scalarTemplateGenerator = new class () extends ScalarTemplateGenerator {
            protected function scalar(Type $type, string $accessor, array $context): string
            {
                return 'SCALAR'.PHP_EOL;
            }
        };

        $template = $scalarTemplateGenerator->generate(new Type('int'), '$accessor', $this->context());

        $this->assertSame([
            'SCALAR',
        ], $this->lines($template));
    }
}
