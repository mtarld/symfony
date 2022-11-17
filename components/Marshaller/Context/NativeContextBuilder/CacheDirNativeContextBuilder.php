<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\NativeContextBuilder;

use Symfony\Component\Marshaller\Context\Context;

final class CacheDirNativeContextBuilder implements NativeContextBuilderInterface
{
    public function __construct(
        private readonly string $cacheDir,
    ) {
    }

    public function build(string $format, Context $context, array $nativeContext): array
    {
        $nativeContext['cache_dir'] = $this->cacheDir;

        return $nativeContext;
    }
}
