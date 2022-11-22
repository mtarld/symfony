<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

use PHPUnit\Framework\TestCase;

abstract class TemplateGeneratorTestCase extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    protected function context(): array
    {
        return ['indentation_level' => 0];
    }

    /**
     * @return list<string>
     */
    protected function lines(string $template): array
    {
        $lines = explode("\n", $template);
        array_pop($lines);

        return $lines;
    }
}
