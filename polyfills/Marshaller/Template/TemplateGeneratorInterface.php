<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Template;

use Symfony\Polyfill\Marshaller\Metadata\Type;

interface TemplateGeneratorInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function generateNull(array $context): string;

    /**
     * @param array<string, mixed> $context
     */
    public function generateScalar(Type $type, string $accessor, array $context): string;

    /**
     * @param array<string, mixed> $context
     */
    public function generateObject(Type $type, string $accessor, array $context): string;
}
