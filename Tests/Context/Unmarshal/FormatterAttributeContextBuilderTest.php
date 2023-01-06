<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Context\Unmarshal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Unmarshal\FormatterAttributeContextBuilder;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithFormatterAttributes;

final class FormatterAttributeContextBuilderTest extends TestCase
{
    public function testAddPropertyFormattersToContext(): void
    {
        $rawContext = (new FormatterAttributeContextBuilder())->build(DummyWithFormatterAttributes::class, new Context(), []);

        $this->assertEquals([
            'symfony' => [
                'unmarshal' => [
                    'property_formatter' => [
                        sprintf('%s::$%s', DummyWithFormatterAttributes::class, 'id') => DummyWithFormatterAttributes::divideAndCastToInt(...),
                    ],
                ],
            ],
        ], $rawContext);
    }

    public function testSkipOnInvalidClassName(): void
    {
        $rawContext = (new FormatterAttributeContextBuilder())->build('useless', new Context(), []);

        $this->assertSame([], $rawContext);
    }
}
