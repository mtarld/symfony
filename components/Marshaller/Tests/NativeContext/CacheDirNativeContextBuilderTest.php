<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\NativeContext;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\NativeContext\CacheDirNativeContextBuilder;

final class CacheDirNativeContextBuilderTest extends TestCase
{
    public function testAddCacheDirToNativeContext(): void
    {
        $contextBuilder = new CacheDirNativeContextBuilder('cacheDir');

        $this->assertSame(['cache_dir' => 'cacheDir'], $contextBuilder->buildMarshalNativeContext('useless', new Context(), []));
        $this->assertSame(['cache_dir' => 'cacheDir'], $contextBuilder->buildGenerateNativeContext('useless', new Context(), []));
    }
}
