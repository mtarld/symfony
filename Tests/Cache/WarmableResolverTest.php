<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Cache;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Attribute\Warmable;
use Symfony\Component\Marshaller\Cache\WarmableResolver;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\WarmableDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\WarmableNotNullableDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\WarmableNullableDummy;

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
