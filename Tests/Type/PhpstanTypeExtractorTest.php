<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Exception\UnsupportedTypeException;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\AbstractDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\PhpstanExtractableDummy;
use Symfony\Component\Marshaller\Type\PhpstanTypeExtractor;
use Symfony\Component\Marshaller\Type\TypeExtractorInterface;

final class PhpstanTypeExtractorTest extends TestCase
{
    /**
     * @dataProvider typesDataProvider
     */
    public function testExtractFromProperty(string $expectedType, string $property): void
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractFromProperty')->willReturn('FALLBACK');

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);
        $reflectionProperty = (new \ReflectionClass(PhpstanExtractableDummy::class))->getProperty($property);

        $this->assertSame($expectedType, $extractor->extractFromProperty($reflectionProperty));
    }

    public function testCannotHandleIntersectionProperty(): void
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);
        $reflectionProperty = (new \ReflectionClass(PhpstanExtractableDummy::class))->getProperty('intersection');

        $this->expectException(UnsupportedTypeException::class);

        $extractor->extractFromProperty($reflectionProperty);
    }

    public function testCannotHandleUnknownNode(): void
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);
        $reflectionProperty = (new \ReflectionClass(PhpstanExtractableDummy::class))->getProperty('unknown');

        $this->expectException(UnsupportedTypeException::class);

        $extractor->extractFromProperty($reflectionProperty);
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testExtractFromFunctionReturn(string $expectedType, string $method): void
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractFromFunctionReturn')->willReturn('FALLBACK');

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);
        $reflectionMethod = (new \ReflectionClass(PhpstanExtractableDummy::class))->getMethod($method);

        $this->assertSame($expectedType, $extractor->extractFromFunctionReturn($reflectionMethod));
    }

    public function testFallbackOnVoidAndNeverFunctionReturn(): void
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractFromFunctionReturn')->willReturn('FALLBACK');

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);

        $voidReflectionMethod = (new \ReflectionClass(PhpstanExtractableDummy::class))->getMethod('void');
        $neverReflectionMethod = (new \ReflectionClass(PhpstanExtractableDummy::class))->getMethod('never');

        $this->assertSame('FALLBACK', $extractor->extractFromFunctionReturn($voidReflectionMethod));
        $this->assertSame('FALLBACK', $extractor->extractFromFunctionReturn($neverReflectionMethod));
    }

    public function testExtractClassTypeFromFunctionFunctionReturn(): void
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractFromFunctionReturn')->willReturn('FALLBACK');

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);

        /** @return self */
        $selfReflectionFunction = new \ReflectionFunction(function () {
            return $this;
        });

        $this->assertSame(self::class, $extractor->extractFromFunctionReturn($selfReflectionFunction));
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testExtractFromParameter(string $expectedType, string $method): void
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractFromFunctionParameter')->willReturn('FALLBACK');

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);

        $reflectionParameter = (new \ReflectionClass(PhpstanExtractableDummy::class))->getMethod($method)->getParameters()[0];

        $this->assertSame($expectedType, $extractor->extractFromFunctionParameter($reflectionParameter));
    }

    public function testExtractClassTypeFromParameter(): void
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractFromFunctionParameter')->willReturn('FALLBACK');

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);

        /** @param self $_ */
        $selfReflectionFunction = new \ReflectionFunction(function ($_) {
        });

        $this->assertSame(self::class, $extractor->extractFromFunctionParameter($selfReflectionFunction->getParameters()[0]));
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: bool}>
     */
    public function typesDataProvider(): iterable
    {
        yield ['mixed', 'mixed'];
        yield ['bool', 'bool'];
        yield ['bool', 'boolean'];
        yield ['bool', 'true'];
        yield ['bool', 'false'];
        yield ['int', 'int'];
        yield ['int', 'integer'];
        yield ['float', 'float'];
        yield ['string', 'string'];
        yield ['resource', 'resource'];
        yield ['object', 'object'];
        yield ['callable', 'callable'];
        yield ['array', 'array'];
        yield ['array', 'list'];
        yield ['array', 'iterable'];
        yield ['array', 'nonEmptyArray'];
        yield ['array', 'nonEmptyList'];
        yield ['null', 'null'];
        yield [PhpstanExtractableDummy::class, 'self'];
        yield [PhpstanExtractableDummy::class, 'static'];
        yield [AbstractDummy::class, 'parent'];
        yield ['Symfony\\Component\\Marshaller\\Tests\\Fixtures\\Dto\\scoped', 'scoped'];
        yield ['int|string', 'union'];
        yield ['?int', 'nullable'];
        yield ['int|string|null', 'nullableUnion'];
        yield ['array<int, string>', 'genericList'];
        yield ['array<int, string>', 'genericArrayList'];
        yield ['array<string, string>', 'genericDict'];
        yield ['array<int, string>', 'squareBracketList'];
        yield ['array<string, int|string>', 'bracketList'];
        yield ['array<string, mixed>', 'emptyBracketList'];
        yield ['ArrayIterator<Tk, Tv>', 'generic'];
        yield ['Tv', 'genericParameter'];
        yield ['FALLBACK', 'undefined'];
    }
}
