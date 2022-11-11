<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\NativeContextBuilder;

use Symfony\Component\Marshaller\Context\Context;

final class CacheDirNativeContextBuilder implements GenerationNativeContextBuilderInterface, MarshalNativeContextBuilderInterface
{
    public function __construct(
        private readonly string $cacheDir,
    ) {
    }

    public function forGeneration(string $type, string $format, Context $context, array $nativeContext): array
    {
        return $this->addCacheDirToNativeContext($nativeContext);
    }

    public function forMarshal(mixed $data, string $format, Context $context, array $nativeContext): array
    {
        return $this->addCacheDirToNativeContext($nativeContext);
    }

    /**
     * @param array<string, mixed> $nativeContext
     *
     * @return array<string, mixed>
     */
    private function addCacheDirToNativeContext(array $nativeContext): array
    {
        $nativeContext['cache_dir'] = $this->cacheDir;

        return $nativeContext;
    }
}
