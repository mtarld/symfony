<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Context\ContextBuilder\Unmarshal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\ContextBuilder\Unmarshal\FormatterAttributeContextBuilder;
use Symfony\Component\Marshaller\MarshallableResolverInterface;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\AnotherDummyWithFormatterAttributes;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithFormatterAttributes;

final class FormatterAttributeContextBuilderTest extends TestCase
{
    public function testAddPropertyFormattersToContext(): void
    {
        $marshallableResolver = $this->createStub(MarshallableResolverInterface::class);
        $marshallableResolver->method('resolve')->willReturn($this->getMarshallable());

        $rawContext = (new FormatterAttributeContextBuilder($marshallableResolver))->build(
            sprintf('%s<%s>', DummyWithFormatterAttributes::class, AnotherDummyWithFormatterAttributes::class),
            new Context(),
            [],
        );

        $this->assertEquals([
            '_symfony' => [
                'unmarshal' => [
                    'property_formatter' => [
                        sprintf('%s::$id', DummyWithFormatterAttributes::class) => [DummyWithFormatterAttributes::class, 'divideAndCastToInt'],
                        sprintf('%s::$name', AnotherDummyWithFormatterAttributes::class) => [AnotherDummyWithFormatterAttributes::class, 'lowercase'],
                    ],
                ],
            ],
        ], $rawContext);
    }

    /**
     * @return \Generator<class-string, null>
     */
    private function getMarshallable(): \Generator
    {
        yield DummyWithFormatterAttributes::class => null;
        yield AnotherDummyWithFormatterAttributes::class => null;
    }
}
