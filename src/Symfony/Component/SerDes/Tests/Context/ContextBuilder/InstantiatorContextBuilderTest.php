<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Context\ContextBuilder;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Context\ContextBuilder\InstantiatorContextBuilder;
use Symfony\Component\SerDes\Exception\InvalidArgumentException;
use Symfony\Component\SerDes\Instantiator\InstantiatorInterface;

class InstantiatorContextBuilderTest extends TestCase
{
    public function testAddEagerInstantiatorToContextByDefault()
    {
        $instantiator = $this->createStub(InstantiatorInterface::class);
        $context = [];

        $this->assertEquals(['instantiator' => null], (new InstantiatorContextBuilder($instantiator))->buildDeserializeContext($context));
    }

    public function testAddLazyInstantiatorToContext()
    {
        $instantiator = $this->createStub(InstantiatorInterface::class);
        $context = ['instantiator' => 'lazy'];

        $this->assertEquals(['instantiator' => $instantiator(...)], (new InstantiatorContextBuilder($instantiator))->buildDeserializeContext($context));
    }

    public function testAddEagerInstantiatorToContext()
    {
        $instantiator = $this->createStub(InstantiatorInterface::class);
        $context = ['instantiator' => 'eager'];

        $this->assertSame(['instantiator' => null], (new InstantiatorContextBuilder($instantiator))->buildDeserializeContext($context));
    }

    public function testAddCustomInstantiatorToContext()
    {
        $instantiator = $this->createStub(InstantiatorInterface::class);
        $customInstantiator = static function () {};

        $context = ['instantiator' => $customInstantiator];

        $this->assertSame(['instantiator' => $customInstantiator], (new InstantiatorContextBuilder($instantiator))->buildDeserializeContext($context));
    }

    public function testThrowIfInvalidInstantiator()
    {
        $instantiator = $this->createStub(InstantiatorInterface::class);
        $context = ['instantiator' => 'foo'];

        $this->expectException(InvalidArgumentException::class);

        (new InstantiatorContextBuilder($instantiator))->buildDeserializeContext($context);
    }

    public function testSkipWhenSerialize()
    {
        $instantiator = $this->createStub(InstantiatorInterface::class);

        $this->assertSame([], (new InstantiatorContextBuilder($instantiator))->buildSerializeContext([], false));
    }
}
