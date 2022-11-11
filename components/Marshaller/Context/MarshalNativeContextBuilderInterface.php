<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context;

interface MarshalNativeContextBuilderInterface
{
    /**
     * @param array<string, mixed>
     *
     * @return array<string, mixed>
     */
    public function forMarshal(\ReflectionClass $class, string $format, array $nativeContext): array;
}
