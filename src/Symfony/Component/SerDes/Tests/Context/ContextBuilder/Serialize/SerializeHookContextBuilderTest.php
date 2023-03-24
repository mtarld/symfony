<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Context\ContextBuilder\Serialize;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Context\ContextBuilder\Serialize\SerializeHookContextBuilder;

class SerializeHookContextBuilderTest extends TestCase
{
    public function testAddHooksToContext()
    {
        $hook = static function () {};
        $contextBuilder = new SerializeHookContextBuilder(['object' => $hook], ['object' => $hook]);

        $this->assertSame(['hooks' => ['serialize' => ['object' => $hook]]], $contextBuilder->build([]));
    }

    public function testSkipWhenWontGenerateTemplate()
    {
        $hook = static function () {};
        $contextBuilder = new SerializeHookContextBuilder(['object' => $hook], ['object' => $hook]);

        $this->assertSame(['template_exists' => true], $contextBuilder->build(['template_exists' => true]));
    }
}
