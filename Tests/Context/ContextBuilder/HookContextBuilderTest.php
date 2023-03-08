<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Context\ContextBuilder;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\ContextBuilder\HookContextBuilder;

final class HookContextBuilderTest extends TestCase
{
    public function testAddHooksToContext(): void
    {
        $hook = static function () {};
        $contextBuilder = new HookContextBuilder(['object' => $hook], ['object' => $hook]);

        $expectedContext = [
            'hooks' => ['object' => $hook],
        ];

        $this->assertSame($expectedContext, $contextBuilder->buildMarshalContext([], true));
        $this->assertSame($expectedContext, $contextBuilder->buildUnmarshalContext([]));
    }

    public function testSkipWhenWontGenerateTemplate(): void
    {
        $hook = static function () {};
        $contextBuilder = new HookContextBuilder(['object' => $hook], ['object' => $hook]);

        $this->assertSame([], $contextBuilder->buildMarshalContext([], false));
    }
}
