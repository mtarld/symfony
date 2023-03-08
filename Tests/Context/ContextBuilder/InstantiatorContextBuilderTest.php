<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Context\ContextBuilder;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\ContextBuilder\InstantiatorContextBuilder;
use Symfony\Component\Marshaller\Exception\InvalidArgumentException;
use Symfony\Component\Marshaller\Instantiator\InstantiatorInterface;

final class InstantiatorContextBuilderTest extends TestCase
{
    public function testAddLazyInstantiatorToContextByDefault(): void
    {
        $instantiator = $this->createStub(InstantiatorInterface::class);
        $context = [];

        $this->assertEquals(['instantiator' => $instantiator(...)], (new InstantiatorContextBuilder($instantiator))->buildUnmarshalContext($context));
    }

    public function testAddLazyInstantiatorToContext(): void
    {
        $instantiator = $this->createStub(InstantiatorInterface::class);
        $context = ['instantiator' => 'lazy'];

        $this->assertEquals(['instantiator' => $instantiator(...)], (new InstantiatorContextBuilder($instantiator))->buildUnmarshalContext($context));
    }

    public function testAddEagerInstantiatorToContext(): void
    {
        $instantiator = $this->createStub(InstantiatorInterface::class);
        $context = ['instantiator' => 'eager'];

        $this->assertSame(['instantiator' => null], (new InstantiatorContextBuilder($instantiator))->buildUnmarshalContext($context));
    }

    public function testAddCustomInstantiatorToContext(): void
    {
        $instantiator = $this->createStub(InstantiatorInterface::class);
        $customInstantiator = static function () {};

        $context = ['instantiator' => $customInstantiator];

        $this->assertSame(['instantiator' => $customInstantiator], (new InstantiatorContextBuilder($instantiator))->buildUnmarshalContext($context));
    }

    public function testThrowIfInvalidInstantiator(): void
    {
        $instantiator = $this->createStub(InstantiatorInterface::class);
        $context = ['instantiator' => 'foo'];

        $this->expectException(InvalidArgumentException::class);

        (new InstantiatorContextBuilder($instantiator))->buildUnmarshalContext($context);
    }

    public function testSkipWhenMarshal(): void
    {
        $instantiator = $this->createStub(InstantiatorInterface::class);

        $this->assertSame([], (new InstantiatorContextBuilder($instantiator))->buildMarshalContext([], false));
    }
}
