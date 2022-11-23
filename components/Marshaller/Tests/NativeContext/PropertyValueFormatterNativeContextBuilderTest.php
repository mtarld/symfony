<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\NativeContext;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\PropertyValueFormatterOption;
use Symfony\Component\Marshaller\NativeContext\PropertyValueFormatterNativeContextBuilder;
use Symfony\Component\Marshaller\Tests\Fixtures\ClassicDummy;

final class PropertyValueFormatterNativeContextBuilderTest extends TestCase
{
    public function testAddPropertyValueFormattersToNativeContext(): void
    {
        $contextBuilder = new PropertyValueFormatterNativeContextBuilder();

        $propertyValueFormatterOption = new PropertyValueFormatterOption([
            ClassicDummy::class => [
                'id' => $idFormatter = fn ($id) => $id + 1,
            ],
        ]);

        $marshalNativeContext = $contextBuilder->buildMarshalNativeContext('useless', new Context($propertyValueFormatterOption), []);
        $generateNativeContext = $contextBuilder->buildGenerateNativeContext('useless', new Context($propertyValueFormatterOption), []);

        $expectedNativeContext = [
            'symfony' => [
                'property_value_formatter' => [
                    sprintf('%s::$id', ClassicDummy::class) => $idFormatter,
                ],
            ],
        ];

        $this->assertSame($expectedNativeContext, $contextBuilder->buildMarshalNativeContext('useless', new Context($propertyValueFormatterOption), []));
        $this->assertSame($expectedNativeContext, $contextBuilder->buildGenerateNativeContext('useless', new Context($propertyValueFormatterOption), []));
    }

    public function testSkipOnMissingPropertyValueFormatterOption(): void
    {
        $contextBuilder = new PropertyValueFormatterNativeContextBuilder();

        $this->assertSame([], $contextBuilder->buildMarshalNativeContext('useless', new Context(), []));
        $this->assertSame([], $contextBuilder->buildGenerateNativeContext('useless', new Context(), []));
    }
}
