<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\NativeContext;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\NativeContext\FormatterAttributeNativeContextBuilder;
use Symfony\Component\Marshaller\Tests\Fixtures\DummyWithFormatterAttributes;

final class FormatterAttributeNativeContextBuilderTest extends TestCase
{
    public function testAddPropertyFormattersToNativeContext(): void
    {
        $nativeContext = (new FormatterAttributeNativeContextBuilder())->build(DummyWithFormatterAttributes::class, new Context(), []);

        $this->assertEquals([
            'symfony' => [
                'property_formatter' => [
                    sprintf('%s::$id', DummyWithFormatterAttributes::class) => DummyWithFormatterAttributes::doubleAndCastToString(...),
                ],
            ],
        ], $nativeContext);
    }

    public function testSkipOnInvalidClassName(): void
    {
        $nativeContext = (new FormatterAttributeNativeContextBuilder())->build('useless', new Context(), []);

        $this->assertSame([], $nativeContext);
    }
}
