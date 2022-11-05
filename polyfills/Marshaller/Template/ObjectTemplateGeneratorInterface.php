<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Template;

interface ObjectTemplateGeneratorInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function generate(\ReflectionClass $class, string $accessor, array $context): string;
}
