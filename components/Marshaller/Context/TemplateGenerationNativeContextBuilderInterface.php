<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context;

interface TemplateGenerationNativeContextBuilderInterface
{
    /**
     * @param array<string, mixed>
     *
     * @return array<string, mixed>
     */
    public function forTemplateGeneration(\ReflectionClass $class, string $format, array $nativeContext): array;
}
