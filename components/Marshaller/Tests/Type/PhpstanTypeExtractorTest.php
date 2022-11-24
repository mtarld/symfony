<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Tests\Fixtures\AbstractDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\PhpstanExtractableDummy;
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
        $fallbackExtractor->method('extractFromProperty')->willReturn('FALLBACK');

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);

        $reflectionProperty = (new \ReflectionClass(PhpstanExtractableDummy::class))->getProperty('intersection');

        $this->expectException(\LogicException::class);
        $this->expectErrorMessage('Cannot handle intersection types.');

        $extractor->extractFromProperty($reflectionProperty);
    }

    public function testCannotHandleNonArrayGenericProperty(): void
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractFromProperty')->willReturn('FALLBACK');

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);

        $reflectionProperty = (new \ReflectionClass(PhpstanExtractableDummy::class))->getProperty('nonArrayGeneric');

        $this->expectException(\LogicException::class);
        $this->expectErrorMessage('Unhandled "ArrayIterator<T>" generic type.');

        $extractor->extractFromProperty($reflectionProperty);
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testExtractFromReturnType(string $expectedType, string $method): void
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractFromReturnType')->willReturn('FALLBACK');

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);

        $reflectionMethod = (new \ReflectionClass(PhpstanExtractableDummy::class))->getMethod($method);

        $this->assertSame($expectedType, $extractor->extractFromReturnType($reflectionMethod));
    }

    public function testFallbackOnVoidAndNeverReturnType(): void
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractFromReturnType')->willReturn('FALLBACK');

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);

        $voidReflectionMethod = (new \ReflectionClass(PhpstanExtractableDummy::class))->getMethod('void');
        $neverReflectionMethod = (new \ReflectionClass(PhpstanExtractableDummy::class))->getMethod('never');

        $this->assertSame('FALLBACK', $extractor->extractFromReturnType($voidReflectionMethod));
        $this->assertSame('FALLBACK', $extractor->extractFromReturnType($neverReflectionMethod));
    }

    public function testExtractClassTypeFromFunctionReturnType(): void
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractFromReturnType')->willReturn('FALLBACK');

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);

        /** @return self */
        $selfReflectionFunction = new \ReflectionFunction(function () {
            return $this;
        });

        $this->assertSame(self::class, $extractor->extractFromReturnType($selfReflectionFunction));
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
        yield ['Symfony\\Component\\Marshaller\\Tests\\Fixtures\\scoped', 'scoped'];
        yield ['int|string', 'union'];
        yield ['?int', 'nullable'];
        yield ['int|string|null', 'nullableUnion'];
        yield ['array<int, string>', 'genericList'];
        yield ['array<int, string>', 'genericArrayList'];
        yield ['array<string, string>', 'genericDict'];
        yield ['array<int, string>', 'squareBracketList'];
        yield ['array<string, int|string>', 'bracketList'];
        yield ['array<string, mixed>', 'emptyBracketList'];
        yield [PhpstanExtractableDummy::class, 'this'];
        yield ['FALLBACK', 'undefined'];
    }
}
