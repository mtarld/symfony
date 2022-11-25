<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\NativeContext;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\HookOption;
use Symfony\Component\Marshaller\NativeContext\HookNativeContextBuilder;

final class HookNativeContextBuilderTest extends TestCase
{
    public function testAddHooksToNativeContext(): void
    {
        $hookOption = new HookOption([
            'foo' => $fooHook = fn () => 'foo',
            'bar' => $barHook = fn () => 'bar',
        ]);

        $nativeContext = (new HookNativeContextBuilder())->build('useless', new Context($hookOption), []);

        $this->assertSame([
            'hooks' => [
                'foo' => $fooHook,
                'bar' => $barHook,
            ],
        ], $nativeContext);
    }

    public function testSkipOnMissingHookOption(): void
    {
        $nativeContext = (new HookNativeContextBuilder())->build('useless', new Context(), []);

        $this->assertSame([], $nativeContext);
    }
}
