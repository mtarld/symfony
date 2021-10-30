<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Context;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Context\ContextBuilderTrait;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
class ContextBuilderTraitTest extends TestCase
{
    public function testConstructor()
    {
        $contextBuilder = new class(['foo' => 'bar']) {
            use ContextBuilderTrait;
        };

        $context = $contextBuilder->toArray();

        $this->assertSame(['foo' => 'bar'], $context);
    }

    public function testWither()
    {
        $contextBuilder = new class() {
            use ContextBuilderTrait;

            public function withFoo(string $value): static
            {
                return $this->with('foo', $value);
            }
        };

        $context = $contextBuilder->withFoo('bar')->toArray();

        $this->assertSame(['foo' => 'bar'], $context);
    }
}
