<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\NativeContext;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\TypeValueFormatterOption;
use Symfony\Component\Marshaller\NativeContext\TypeValueFormatterNativeContextBuilder;

final class TypeValueFormatterNativeContextBuilderTest extends TestCase
{
    public function testAddTypeValueFormattersToNativeContext(): void
    {
        $contextBuilder = new TypeValueFormatterNativeContextBuilder();

        $typeValueFormatterOption = new TypeValueFormatterOption([
            'int' => $idFormatter = fn (int $value) => $value * 2,
        ]);

        $expectedNativeContext = [
            'symfony' => [
                'type_value_formatter' => [
                    'int' => $idFormatter,
                ],
            ],
        ];

        $this->assertSame($expectedNativeContext, $contextBuilder->buildMarshalNativeContext('useless', new Context($typeValueFormatterOption), []));
        $this->assertSame($expectedNativeContext, $contextBuilder->buildGenerateNativeContext('useless', new Context($typeValueFormatterOption), []));
    }

    public function testSkipOnMissingTypeOption(): void
    {
        $contextBuilder = new TypeValueFormatterNativeContextBuilder();

        $this->assertSame([], $contextBuilder->buildMarshalNativeContext('useless', new Context(), []));
        $this->assertSame([], $contextBuilder->buildGenerateNativeContext('useless', new Context(), []));
    }
}
