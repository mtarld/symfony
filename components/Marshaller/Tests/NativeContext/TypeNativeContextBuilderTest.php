<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\NativeContext;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\TypeOption;
use Symfony\Component\Marshaller\NativeContext\TypeNativeContextBuilder;

final class TypeNativeContextBuilderTest extends TestCase
{
    public function testAddTypeToNativeContext(): void
    {
        $typeOption = new TypeOption('?bool');

        $nativeContext = (new TypeNativeContextBuilder())->buildMarshalNativeContext('useless', new Context($typeOption), []);

        $this->assertSame([
            'type' => '?bool',
        ], $nativeContext);
    }

    public function testSkipOnMissingTypeOption(): void
    {
        $nativeContext = (new TypeNativeContextBuilder())->buildMarshalNativeContext('useless', new Context(), []);

        $this->assertSame([], $nativeContext);
    }
}
