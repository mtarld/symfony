<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext;

use Symfony\Component\Marshaller\Context\Context;

final class CacheDirNativeContextBuilder implements MarshalNativeContextBuilderInterface, GenerateNativeContextBuilderInterface
{
    public function __construct(
        private readonly string $cacheDir,
    ) {
    }

    public function buildMarshalNativeContext(string $type, Context $context, array $nativeContext): array
    {
        return $this->addCacheDir($nativeContext);
    }

    public function buildGenerateNativeContext(string $type, Context $context, array $nativeContext): array
    {
        return $this->addCacheDir($nativeContext);
    }

    /**
     * @param array<string, mixed> $nativeContext
     *
     * @return array<string, mixed>
     */
    private function addCacheDir(array $nativeContext): array
    {
        $nativeContext['cache_dir'] = $this->cacheDir;

        return $nativeContext;
    }
}
