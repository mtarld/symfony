<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template;

/**
 * @internal
 */
abstract class NullTemplateGenerator
{
    use PhpWriterTrait;

    /**
     * @param array<string, mixed> $context
     */
    abstract protected function null(array $context): string;

    /**
     * @param array<string, mixed> $context
     */
    public function generate(array $context): string
    {
        return $this->null($context);
    }
}
