<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\NativeContext\Generation;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\NativeContext\Generation\NameAttributeNativeContextBuilder;
use Symfony\Component\Marshaller\Tests\Fixtures\DummyWithNameAttributes;

final class NameAttributeNativeContextBuilderTest extends TestCase
{
    public function testAddPropertyNameToNativeContext(): void
    {
        $nativeContext = (new NameAttributeNativeContextBuilder())->build(DummyWithNameAttributes::class, new Context(), []);

        $this->assertEquals([
            'symfony' => [
                'property_name' => [
                    sprintf('%s::$id', DummyWithNameAttributes::class) => '@id',
                ],
            ],
        ], $nativeContext);
    }

    public function testSkipOnInvalidClassName(): void
    {
        $nativeContext = (new NameAttributeNativeContextBuilder())->build('int', new Context(), []);

        $this->assertSame([], $nativeContext);
    }
}
