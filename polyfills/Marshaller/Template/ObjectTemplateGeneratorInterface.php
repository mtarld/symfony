<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Template;

/**
 * @internal
 */
interface ObjectTemplateGeneratorInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public static function generate(\ReflectionClass $class, string $accessor, array $context): string;
}
