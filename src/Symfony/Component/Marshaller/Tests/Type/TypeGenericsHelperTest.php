<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Exception\InvalidTypeException;
use Symfony\Component\Marshaller\Type\TypeGenericsHelper;

class TypeGenericsHelperTest extends TestCase
{
    /**
     * @dataProvider replaceGenericTypesDataProvider
     *
     * @param array<string, string> $genericTypes
     */
    public function testReplaceGenericTypes(string $expectedType, string $type, array $genericTypes)
    {
        $this->assertSame($expectedType, (new TypeGenericsHelper())->replaceGenericTypes($type, $genericTypes));
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: array<string, string>}>
     */
    public function replaceGenericTypesDataProvider(): iterable
    {
        yield ['T', 'T', []];
        yield ['Foo', 'T', ['T' => 'Foo']];

        yield ['array<int, Foo>', 'array<int, T>', ['T' => 'Foo']];
        yield ['array<Foo>', 'array<T>', ['T' => 'Foo']];
        yield ['array<Foo, Foo>', 'array<T, T>', ['T' => 'Foo']];

        yield ['int|Foo', 'int|T', ['T' => 'Foo']];
        yield ['int|Foo|Bar', 'int|T|U', ['T' => 'Foo', 'U' => 'Bar']];
        yield ['int|Foo|array<string, Bar>', 'int|T|array<string, U>', ['T' => 'Foo', 'U' => 'Bar']];
    }

    /**
     * @dataProvider extractGenericsDataProvider
     *
     * @param list<string> $expectedGenericParameters
     */
    public function testExtractGenerics(string $expectedGenericType, array $expectedGenericParameters, string $type)
    {
        $this->assertSame(
            ['genericType' => $expectedGenericType, 'genericParameters' => $expectedGenericParameters],
            (new TypeGenericsHelper())->extractGenerics($type),
        );
    }

    /**
     * @return iterable<array{0: string, 1: list<string>, 2: string}>
     */
    public function extractGenericsDataProvider(): iterable
    {
        yield ['int', [], 'int'];
        yield ['Foo', ['int'], 'Foo<int>'];
        yield ['array', ['int', 'string'], 'array<int, string>'];
    }

    public function testExtractGenericsThrowOnInvalidGenericString()
    {
        $this->expectException(InvalidTypeException::class);

        (new TypeGenericsHelper())->extractGenerics('Foo<int, Bar<string>', '$accessor', []);
    }
}
