<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\SerializableResolver;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\SerializableResolver\PathSerializableResolver;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\AbstractDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;

class PathSerializableResolverTest extends TestCase
{
    public function testResolve()
    {
        $serializableResolver = new PathSerializableResolver([\dirname(__DIR__, 1).'/Fixtures']);
        $serializableList = iterator_to_array($serializableResolver->resolve());

        $this->assertContains(ClassicDummy::class, $serializableList);
        $this->assertNotContains(AbstractDummy::class, $serializableList);
    }
}
