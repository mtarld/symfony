<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Attribute\Marshallable;
use Symfony\Component\Marshaller\MarshallableResolver;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\AbstractDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\NotMarshallableDummy;

class MarshallableResolverTest extends TestCase
{
    public function testResolve()
    {
        $marshallableResolver = new MarshallableResolver([__DIR__.'/Fixtures']);
        $marshallableList = iterator_to_array($marshallableResolver->resolve());

        $this->assertContainsOnlyInstancesOf(Marshallable::class, $marshallableList);

        $this->assertArrayHasKey(ClassicDummy::class, $marshallableList);
        $this->assertArrayNotHasKey(NotMarshallableDummy::class, $marshallableList);
        $this->assertArrayNotHasKey(AbstractDummy::class, $marshallableList);
    }
}
