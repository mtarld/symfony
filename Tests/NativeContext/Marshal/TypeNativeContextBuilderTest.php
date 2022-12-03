<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\NativeContext\Generation;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\TypeOption;
use Symfony\Component\Marshaller\NativeContext\Marshal\TypeNativeContextBuilder;

final class TypeNativeContextBuilderTest extends TestCase
{
    public function testAddTypeToNativeContext(): void
    {
        $contextBuilder = new TypeNativeContextBuilder();

        $typeOption = new TypeOption('array<int, string>');

        $expectedNativeContext = ['type' => 'array<int, string>'];

        $this->assertSame($expectedNativeContext, $contextBuilder->build(new Context($typeOption), []));
    }

    public function testSkipOnMissingTypeOption(): void
    {
        $contextBuilder = new TypeNativeContextBuilder();

        $this->assertSame([], $contextBuilder->build(new Context(), []));
    }
}
