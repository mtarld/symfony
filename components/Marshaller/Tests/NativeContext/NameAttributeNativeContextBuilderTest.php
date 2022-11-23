<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\NativeContext;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\NativeContext\NameAttributeNativeContextBuilder;
use Symfony\Component\Marshaller\Tests\Fixtures\DummyWithNameAttributes;

final class NameAttributeNativeContextBuilderTest extends TestCase
{
    public function testAddPropertyNameToNativeContext(): void
    {
        $nativeContext = (new NameAttributeNativeContextBuilder())->buildGenerateNativeContext(DummyWithNameAttributes::class, new Context(), []);

        $this->assertEquals([
            'symfony' => [
                'property_name' => [
                    sprintf('%s::$id', DummyWithNameAttributes::class) => '@id',
                    sprintf('%s::$enabled', DummyWithNameAttributes::class) => 'active',
                ],
            ],
        ], $nativeContext);
    }

    public function testSkipOnInvalidClassName(): void
    {
        $nativeContext = (new NameAttributeNativeContextBuilder())->buildGenerateNativeContext('int', new Context(), []);

        $this->assertSame([], $nativeContext);
    }
}
