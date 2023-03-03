<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Marshal\Json;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Marshal\Json\JsonSyntax;

final class JsonSyntaxTest extends TestCase
{
    public function testEscapeString(): void
    {
        $jsonSyntax = new JsonSyntax();

        $this->assertSame('foo', $jsonSyntax->escapeString('foo'));
        $this->assertSame('f\"oo', $jsonSyntax->escapeString('f"oo'));
        $this->assertSame('f\\\\\"oo', $jsonSyntax->escapeString('f\\"oo'));
    }
}
