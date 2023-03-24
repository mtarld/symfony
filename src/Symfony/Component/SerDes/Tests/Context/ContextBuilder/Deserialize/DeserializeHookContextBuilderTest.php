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
use Symfony\Component\SerDes\Context\ContextBuilder\Deserialize\DeserializeHookContextBuilder;

class DeserializeHookContextBuilderTest extends TestCase
{
    public function testAddHooksToContext()
    {
        $hook = static function () {};
        $contextBuilder = new DeserializeHookContextBuilder(['object' => $hook], ['object' => $hook]);

        $this->assertSame(['hooks' => ['deserialize' => ['object' => $hook]]], $contextBuilder->build([]));
    }
}
