<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template;

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
        if ('' === $content || "''" === $content) {
            return '';
        }

        return $this->writeLine(sprintf('\fwrite($resource, %s);', $content), $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function writeLine(string $line, array $context): string
    {
        return str_repeat(' ', 4 * $context['indentation_level']).$line.PHP_EOL;
    }
}
