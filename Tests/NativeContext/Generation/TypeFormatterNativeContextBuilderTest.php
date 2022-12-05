<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\NativeContext\Generation;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\TypeFormatterOption;
use Symfony\Component\Marshaller\NativeContext\Generation\TypeFormatterNativeContextBuilder;

final class TypeFormatterNativeContextBuilderTest extends TestCase
{
    public function testAddTypeFormattersToNativeContext(): void
    {
        $contextBuilder = new TypeFormatterNativeContextBuilder();

        $typeFormatterOption = new TypeFormatterOption([
            'int' => $idFormatter = fn (int $value) => $value * 2,
        ]);

        $expectedNativeContext = [
            'symfony' => [
                'marshal' => [
                    'type_formatter' => [
                        'int' => $idFormatter,
                    ],
                ],
            ],
        ];

        $this->assertSame($expectedNativeContext, $contextBuilder->build('useless', new Context($typeFormatterOption), []));
    }

    public function testSkipOnMissingTypeOption(): void
    {
        $contextBuilder = new TypeFormatterNativeContextBuilder();

        $this->assertSame([], $contextBuilder->build('useless', new Context(), []));
    }
}
