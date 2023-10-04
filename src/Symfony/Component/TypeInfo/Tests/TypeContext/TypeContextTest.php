<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo\Tests\TypeContext;

use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\Exception\LogicException;
use Symfony\Component\TypeInfo\Tests\Fixtures\AbstractDummy;
use Symfony\Component\TypeInfo\Tests\Fixtures\Dummy;
use Symfony\Component\TypeInfo\Tests\Fixtures\DummyExtendingStdClass;
use Symfony\Component\TypeInfo\Tests\Fixtures\DummyWithUses;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeContext\TypeContext;
use Symfony\Component\TypeInfo\TypeContext\TypeContextFactory;

class TypeContextTest extends TestCase
{
    public function testResolve()
    {
        $typeContext = (new TypeContextFactory())->createFromClassName(DummyWithUses::class);

        $this->assertSame(DummyWithUses::class, $typeContext->resolve('DummyWithUses'));
        $this->assertSame(Type::class, $typeContext->resolve('Type'));
        $this->assertSame(\DateTimeImmutable::class, $typeContext->resolve('DateTime'));
        $this->assertSame('Symfony\\Component\\TypeInfo\\Tests\\Fixtures\\unknown', $typeContext->resolve('unknown'));
        $this->assertSame('unknown', $typeContext->resolve('\\unknown'));

        $typeContextWithoutNamespace = new TypeContext('Foo', 'Bar');
        $this->assertSame('unknown', $typeContextWithoutNamespace->resolve('unknown'));
    }

    public function testResolveDeclaringClass()
    {
        $this->assertSame(Dummy::class, (new TypeContextFactory())->createFromClassName(Dummy::class)->resolveDeclaringClass());
        $this->assertSame(AbstractDummy::class, (new TypeContextFactory())->createFromClassName(Dummy::class, AbstractDummy::class)->resolveDeclaringClass());
    }

    public function testResolveCalledClass()
    {
        $this->assertSame(Dummy::class, (new TypeContextFactory())->createFromClassName(Dummy::class)->resolveCalledClass());
        $this->assertSame(Dummy::class, (new TypeContextFactory())->createFromClassName(Dummy::class, AbstractDummy::class)->resolveCalledClass());
    }

    public function testResolveParentClass()
    {
        $this->assertSame(AbstractDummy::class, (new TypeContextFactory())->createFromClassName(Dummy::class)->resolveParentClass());
        $this->assertSame(\stdClass::class, (new TypeContextFactory())->createFromClassName(DummyExtendingStdClass::class)->resolveParentClass());
    }

    public function testCannotResolveParentClassWhenDoNotInherit()
    {
        $this->expectException(LogicException::class);
        (new TypeContextFactory())->createFromClassName(AbstractDummy::class)->resolveParentClass();
    }
}
