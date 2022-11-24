<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Cache;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Attribute\Warmable;
use Symfony\Component\Marshaller\Cache\WarmableResolver;
use Symfony\Component\Marshaller\Tests\Fixtures\WarmableDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\WarmableNotNullableDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\WarmableNullableDummy;

final class WarmableResolverTest extends TestCase
{
    public function testResolve(): void
    {
        $warmableResolver = new WarmableResolver([__DIR__.'/../Fixtures']);

        $this->assertEquals([
            WarmableDummy::class => new Warmable(nullable: null),
            WarmableNullableDummy::class => new Warmable(nullable: true),
            WarmableNotNullableDummy::class => new Warmable(nullable: false),
        ], iterator_to_array($warmableResolver->resolve()));
    }
}
