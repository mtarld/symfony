<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\NativeContext;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\TypeFormatterOption;
use Symfony\Component\Marshaller\NativeContext\TypeFormatterNativeContextBuilder;

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
                'type_formatter' => [
                    'int' => $idFormatter,
                ],
            ],
        ];

        $this->assertSame($expectedNativeContext, $contextBuilder->buildGenerateNativeContext('useless', new Context($typeFormatterOption), []));
    }

    public function testSkipOnMissingTypeOption(): void
    {
        $contextBuilder = new TypeFormatterNativeContextBuilder();

        $this->assertSame([], $contextBuilder->buildGenerateNativeContext('useless', new Context(), []));
    }
}
