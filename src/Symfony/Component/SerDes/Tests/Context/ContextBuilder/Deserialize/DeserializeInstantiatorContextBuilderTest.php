<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Context\ContextBuilder\Deserialize;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Context\ContextBuilder\Deserialize\DeserializeInstantiatorContextBuilder;
use Symfony\Component\SerDes\Exception\InvalidArgumentException;
use Symfony\Component\SerDes\Instantiator\InstantiatorInterface;

class DeserializeInstantiatorContextBuilderTest extends TestCase
{
    public function testAddEagerInstantiatorToContextByDefault()
    {
        $instantiator = $this->createStub(InstantiatorInterface::class);
        $context = [];

        $this->assertEquals(['instantiator' => null], (new DeserializeInstantiatorContextBuilder($instantiator))->build($context));
    }

    public function testAddLazyInstantiatorToContext()
    {
        $instantiator = $this->createStub(InstantiatorInterface::class);
        $context = ['instantiator' => 'lazy'];

        $this->assertEquals(['instantiator' => $instantiator(...)], (new DeserializeInstantiatorContextBuilder($instantiator))->build($context));
    }

    public function testAddEagerInstantiatorToContext()
    {
        $instantiator = $this->createStub(InstantiatorInterface::class);
        $context = ['instantiator' => 'eager'];

        $this->assertSame(['instantiator' => null], (new DeserializeInstantiatorContextBuilder($instantiator))->build($context));
    }

    public function testAddCustomInstantiatorToContext()
    {
        $instantiator = $this->createStub(InstantiatorInterface::class);
        $customInstantiator = static function () {};

        $context = ['instantiator' => $customInstantiator];

        $this->assertSame(['instantiator' => $customInstantiator], (new DeserializeInstantiatorContextBuilder($instantiator))->build($context));
    }

    public function testThrowIfInvalidInstantiator()
    {
        $instantiator = $this->createStub(InstantiatorInterface::class);
        $context = ['instantiator' => 'foo'];

        $this->expectException(InvalidArgumentException::class);

        (new DeserializeInstantiatorContextBuilder($instantiator))->build($context);
    }
}
