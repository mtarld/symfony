<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Template\Json;

final class ScalarGenerator
{
    public function generate(\ReflectionProperty $reflectionProperty, string $accessor, array $context = []): string
    {
        // TODO hook to change value

        return $this->writeLine("fwrite(\$resource, json_encode($accessor));", $context['indentation_level']);
    }

    private function writeLine(string $php, int $indentationLevel): string
    {
        return sprintf('%s%s%s', str_repeat(' ', 4 * $indentationLevel), $php, PHP_EOL);
    }
}


