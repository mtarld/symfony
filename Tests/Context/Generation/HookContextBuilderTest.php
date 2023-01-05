<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Context\Generation;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Generation\HookContextBuilder;
use Symfony\Component\Marshaller\Context\Option\HookOption;

final class HookContextBuilderTest extends TestCase
{
    public function testAddHooksToContext(): void
    {
        $hookOption = new HookOption([
            'foo' => $fooHook = fn () => 'foo',
            'bar' => $barHook = fn () => 'bar',
        ]);

        $rawContext = (new HookContextBuilder())->build('useless', new Context($hookOption), []);

        $this->assertSame([
            'hooks' => [
                'foo' => $fooHook,
                'bar' => $barHook,
            ],
        ], $rawContext);
    }

    public function testSkipOnMissingHookOption(): void
    {
        $rawContext = (new HookContextBuilder())->build('useless', new Context(), []);

        $this->assertSame([], $rawContext);
    }
}
