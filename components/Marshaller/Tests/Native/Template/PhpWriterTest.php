<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

use Symfony\Component\Marshaller\Native\Template\PhpWriterTrait;

final class PhpWriterTest extends TemplateGeneratorTestCase
{
    public function testFwrite(): void
    {
        $templateGenerator = new class () {
            use PhpWriterTrait {
                fwrite as private doFwrite;
            }

            public function fwrite(string $content, array $context): string
            {
                return $this->doFwrite($content, $context);
            }
        };

        $context = ['indentation_level' => 0];

        $this->assertSame('', $templateGenerator->fwrite('', $context));
        $this->assertSame('', $templateGenerator->fwrite("''", $context));
        $this->assertSame('\fwrite($resource, foo);'.PHP_EOL, $templateGenerator->fwrite('foo', $context));

        $context['indentation_level'] = 1;

        $this->assertSame('    \fwrite($resource, foo);'.PHP_EOL, $templateGenerator->fwrite('foo', $context));
    }

    public function testWriteLine(): void
    {
        $templateGenerator = new class () {
            use PhpWriterTrait {
                writeLine as private doWriteLine;
            }

            public function writeLine(string $content, array $context): string
            {
                return $this->doWriteLine($content, $context);
            }
        };

        $context = ['indentation_level' => 0];

        $this->assertSame('foo'.PHP_EOL, $templateGenerator->writeLine('foo', $context));

        $context['indentation_level'] = 1;

        $this->assertSame('    foo'.PHP_EOL, $templateGenerator->writeLine('foo', $context));
    }
}
