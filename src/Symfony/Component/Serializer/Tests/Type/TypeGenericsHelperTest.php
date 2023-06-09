<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\Serializer\Type\TypeFactory;
use Symfony\Component\Serializer\Type\TypeGenericsHelper;

class TypeGenericsHelperTest extends TestCase
{
    /**
     * @dataProvider replaceGenericTypesDataProvider
     *
     * @param array<string, string> $genericTypes
     */
    public function testReplaceGenericTypes(string $expectedType, string $type, array $genericTypes)
    {
        $genericTypes = array_map(fn (string $t): Type => TypeFactory::createFromString($t), $genericTypes);

        $this->assertEquals($expectedType, (new TypeGenericsHelper())->replaceGenericTypes(TypeFactory::createFromString($type), $genericTypes));
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: array<string, string>}>
     */
    public static function replaceGenericTypesDataProvider(): iterable
    {
        yield ['T', 'T', []];
        yield ['Foo', 'T', ['T' => 'Foo']];

        yield ['array<int, Foo>', 'array<int, T>', ['T' => 'Foo']];
        yield ['array<Foo, Foo>', 'array<T, T>', ['T' => 'Foo']];

        yield ['int|Foo', 'int|T', ['T' => 'Foo']];
        yield ['int|Foo|Bar', 'int|T|U', ['T' => 'Foo', 'U' => 'Bar']];
        yield ['int|Foo|array<string, Bar>', 'int|T|array<string, U>', ['T' => 'Foo', 'U' => 'Bar']];
    }
}
