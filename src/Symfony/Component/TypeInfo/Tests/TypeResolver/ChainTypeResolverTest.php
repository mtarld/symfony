<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo\Tests\TypeResolver;

use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Exception\UnsupportedException;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeResolver\ChainTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolverInterface;

class ChainTypeResolverTest extends TestCase
{
    public function testResolve()
    {
        $firstResolver = $this->createMock(TypeResolverInterface::class);
        $firstResolver->method('resolve')->willThrowException(new UnsupportedException('cannot resolve.'));

        $secondResolver = $this->createMock(TypeResolverInterface::class);
        $secondResolver->method('resolve')->willReturn(Type::int());

        $thirdResolver = $this->createMock(TypeResolverInterface::class);
        $thirdResolver->method('resolve')->willReturn(Type::string());

        $resolver = new ChainTypeResolver([$firstResolver, $secondResolver, $thirdResolver]);

        $this->assertEquals(Type::int(), $resolver->resolve('useless'));
    }

    public function testCannotResolveIfNoResolverCan()
    {
        $this->expectException(UnsupportedException::class);

        $resolver = new ChainTypeResolver([]);
        $resolver->resolve('int');
    }
}
