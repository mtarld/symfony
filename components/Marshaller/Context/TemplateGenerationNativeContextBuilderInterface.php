<?php

namespace Symfony\Component\Marshaller\Context;

interface TemplateGenerationNativeContextBuilderInterface
{
    /**
     * @param array<string, mixed>
     *
     * @return array<string, mixed>
     */
    public function forTemplateGeneration(\ReflectionClass $class, string $format, Context $context, array $nativeContext): array;
}
