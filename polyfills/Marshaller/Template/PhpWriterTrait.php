<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Template;

/**
 * @internal
 */
trait PhpWriterTrait
{
    /**
     * @param array<string, mixed> $context
     */
    protected function fwrite(string $content, array $context): string
    {
        return $this->writeLine(sprintf('fwrite($resource, %s);', $content), $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function writeLine(string $line, array $context): string
    {
        return str_repeat(' ', 4 * $context['indentation_level']).$line.PHP_EOL;
    }
}
