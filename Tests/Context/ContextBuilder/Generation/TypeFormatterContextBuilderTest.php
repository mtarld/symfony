<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Context\ContextBuilder\Generation;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\ContextBuilder\Generation\TypeFormatterContextBuilder;
use Symfony\Component\Marshaller\Context\Option\TypeFormatterOption;

final class TypeFormatterContextBuilderTest extends TestCase
{
    public function testAddTypeFormattersToContext(): void
    {
        $contextBuilder = new TypeFormatterContextBuilder();

        $typeFormatterOption = new TypeFormatterOption([
            'int' => $idFormatter = fn (int $value) => $value * 2,
        ]);

        $expectedContext = [
            'symfony' => [
                'marshal' => [
                    'type_formatter' => [
                        'int' => $idFormatter,
                    ],
                ],
            ],
        ];

        $this->assertSame($expectedContext, $contextBuilder->build('useless', new Context($typeFormatterOption), []));
    }

    public function testSkipOnMissingTypeOption(): void
    {
        $contextBuilder = new TypeFormatterContextBuilder();

        $this->assertSame([], $contextBuilder->build('useless', new Context(), []));
    }
}
