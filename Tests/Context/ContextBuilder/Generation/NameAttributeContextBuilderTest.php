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
use Symfony\Component\Marshaller\Context\ContextBuilder\Generation\NameAttributeContextBuilder;
use Symfony\Component\Marshaller\MarshallableResolverInterface;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\AnotherDummyWithNameAttributes;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithNameAttributes;

final class NameAttributeContextBuilderTest extends TestCase
{
    public function testAddPropertyNameToContext(): void
    {
        $marshallableResolver = $this->createStub(MarshallableResolverInterface::class);
        $marshallableResolver->method('resolve')->willReturn($this->getMarshallable());

        $rawContext = (new NameAttributeContextBuilder($marshallableResolver))->build(
            sprintf('%s|%s', DummyWithNameAttributes::class, AnotherDummyWithNameAttributes::class),
            new Context(),
            [],
        );

        $this->assertEquals([
            '_symfony' => [
                'marshal' => [
                    'property_name' => [
                        sprintf('%s::$id', DummyWithNameAttributes::class) => '@id',
                        sprintf('%s::$name', AnotherDummyWithNameAttributes::class) => 'call_me_with',
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
        yield DummyWithNameAttributes::class => null;
        yield AnotherDummyWithNameAttributes::class => null;
    }
}
