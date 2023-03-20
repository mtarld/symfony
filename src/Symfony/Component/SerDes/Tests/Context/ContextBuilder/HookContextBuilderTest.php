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
use Symfony\Component\SerDes\Context\ContextBuilder\HookContextBuilder;

class HookContextBuilderTest extends TestCase
{
    public function testAddHooksToContext()
    {
        $hook = static function () {};
        $contextBuilder = new HookContextBuilder(['object' => $hook], ['object' => $hook]);

        $this->assertSame(['hooks' => ['serialize' => ['object' => $hook]]], $contextBuilder->buildSerializeContext([], true));
        $this->assertSame(['hooks' => ['deserialize' => ['object' => $hook]]], $contextBuilder->buildDeserializeContext([]));
    }

    public function testSkipWhenWontGenerateTemplate()
    {
        $hook = static function () {};
        $contextBuilder = new HookContextBuilder(['object' => $hook], ['object' => $hook]);

        $this->assertSame([], $contextBuilder->buildSerializeContext([], false));
    }
}
