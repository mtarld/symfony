<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\NativeContext;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\PropertyNameFormatterOption;
use Symfony\Component\Marshaller\NativeContext\PropertyNameFormatterNativeContextBuilder;
use Symfony\Component\Marshaller\Tests\Fixtures\ClassicDummy;

final class PropertyNameFormatterNativeContextBuilderTest extends TestCase
{
    public function testAddPropertyNameFormattersToNativeContext(): void
    {
        $contextBuilder = new PropertyNameFormatterNativeContextBuilder();

        $propertyNameFormatterOption = new PropertyNameFormatterOption([
            ClassicDummy::class => [
                'id' => $idFormatter = fn () => '@id',
            ],
        ]);

        $expectedNativeContext = [
            'symfony' => [
                'property_name_formatter' => [
                    sprintf('%s::$id', ClassicDummy::class) => $idFormatter,
                ],
            ],
        ];

        $this->assertSame($expectedNativeContext, $contextBuilder->buildMarshalNativeContext('useless', new Context($propertyNameFormatterOption), []));
        $this->assertSame($expectedNativeContext, $contextBuilder->buildGenerateNativeContext('useless', new Context($propertyNameFormatterOption), []));
    }

    public function testSkipOnMissingPropertyNameFormatterOption(): void
    {
        $contextBuilder = new PropertyNameFormatterNativeContextBuilder();

        $this->assertSame([], $contextBuilder->buildMarshalNativeContext('useless', new Context(), []));
        $this->assertSame([], $contextBuilder->buildGenerateNativeContext('useless', new Context(), []));
    }
}
