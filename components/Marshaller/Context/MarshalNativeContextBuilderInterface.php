<?php

namespace Symfony\Component\Marshaller\Context;

interface MarshalNativeContextBuilderInterface
{
    /**
     * @param array<string, mixed>
     *
     * @return array<string, mixed>
     */
    public function forMarshal(\ReflectionClass $class, string $format, ?Context $context, array $nativeContext): array;
}
