<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Hook\Marshal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Exception\InvalidArgumentException;
use Symfony\Component\Marshaller\Hook\Marshal\TypeHook;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithMethods;
use Symfony\Component\Marshaller\Type\TypeExtractorInterface;

final class TypeHookTest extends TestCase
{
    /**
     * @dataProvider updateTypeAndAccessorFromFormatterDataProvider
     *
     * @param array<string, callable> $typeFormatters
     */
    public function testUpdateTypeAndAccessorFromFormatter(string $expectedType, string $expectedAccessor, string $formatterReturnType, array $typeFormatters): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);
        $typeExtractor->method('extractFromFunctionReturn')->willReturn($formatterReturnType);

        $context = [
            '_symfony' => [
                'marshal' => [
                    'type_formatter' => $typeFormatters,
                ],
            ],
        ];

        $hookResult = (new TypeHook($typeExtractor))('int', '$accessor', $context);

        $this->assertSame($expectedType, $hookResult['type']);
        $this->assertSame($expectedAccessor, $hookResult['accessor']);
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: string, 3: array<string, callable>}
     */
    public function updateTypeAndAccessorFromFormatterDataProvider(): iterable
    {
        yield ['int', '$accessor', 'string', []];
        yield ['int', '$accessor', 'string', ['string' => DummyWithMethods::doubleAndCastToString(...)]];
        yield ['string', sprintf('%s::doubleAndCastToString($accessor, $context)', DummyWithMethods::class), 'string', ['int' => DummyWithMethods::doubleAndCastToString(...)]];
    }

    /**
     * @dataProvider throwWhenWrongFormatterDataProvider
     */
    public function testThrowWhenWrongFormatter(string $exceptionMessage, callable $formatter): void
    {
        $typeExtractor = $this->createStub(TypeExtractorInterface::class);

        $context = [
            '_symfony' => [
                'marshal' => [
                    'type_formatter' => ['int' => $formatter],
                ],
            ],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);

        (new TypeHook($typeExtractor))('int', '$accessor', $context);
    }

    /**
     * @return iterable<array{0: string, 1: callable}>
     */
    public function throwWhenWrongFormatterDataProvider(): iterable
    {
        yield [
            'Type formatter "int" must be a static method.',
            (new DummyWithMethods())->nonStatic(...),
        ];

        yield [
            'Return type of type formatter "int" must not be "void" nor "never".',
            DummyWithMethods::void(...),
        ];

        yield [
            'Second argument of type formatter "int" must be an array.',
            DummyWithMethods::invalidContextType(...),
        ];
    }
}
