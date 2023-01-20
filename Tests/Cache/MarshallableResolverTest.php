<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Cache;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Attribute\Marshallable;
use Symfony\Component\Marshaller\Cache\MarshallableResolver;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\MarshallableDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\MarshallableNotNullableDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\MarshallableNullableDummy;

final class MarshallableResolverTest extends TestCase
{
    public function testResolve(): void
    {
        $marshallableResolver = new MarshallableResolver([__DIR__.'/../Fixtures']);

        $this->assertEquals([
            MarshallableDummy::class => new Marshallable(nullable: null),
            MarshallableNullableDummy::class => new Marshallable(nullable: true),
            MarshallableNotNullableDummy::class => new Marshallable(nullable: false),
        ], iterator_to_array($marshallableResolver->resolve()));
    }
}
