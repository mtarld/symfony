<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Generation\NameAttributeContextBuilder;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithNameAttributes;

final class NameAttributeContextBuilderTest extends TestCase
{
    public function testAddPropertyNameToContext(): void
    {
        $rawContext = (new NameAttributeContextBuilder())->build(DummyWithNameAttributes::class, new Context(), []);

        $this->assertEquals([
            'symfony' => [
                'marshal' => [
                    'property_name' => [
                        sprintf('%s::$id', DummyWithNameAttributes::class) => '@id',
                    ],
                ],
            ],
        ], $rawContext);
    }

    public function testSkipOnInvalidClassName(): void
    {
        $rawContext = (new NameAttributeContextBuilder())->build('int', new Context(), []);

        $this->assertSame([], $rawContext);
    }
}
