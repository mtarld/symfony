<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Attribute\Serializable;
use Symfony\Component\SerDes\SerializableResolver;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\AbstractDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\NotSerializableDummy;

class SerializableResolverTest extends TestCase
{
    public function testResolve()
    {
        $serializableResolver = new SerializableResolver([__DIR__.'/Fixtures']);
        $serializableList = iterator_to_array($serializableResolver->resolve());

        $this->assertContainsOnlyInstancesOf(Serializable::class, $serializableList);

        $this->assertArrayHasKey(ClassicDummy::class, $serializableList);
        $this->assertArrayNotHasKey(NotSerializableDummy::class, $serializableList);
        $this->assertArrayNotHasKey(AbstractDummy::class, $serializableList);
    }
}
