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
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\AbstractDummy;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\NonUniqueTemplatePhpstanExtractableDummy;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\PhpstanExtractableDummy;
use Symfony\Component\Serializer\Type\PhpstanTypeExtractor;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;
use Symfony\Component\Serializer\Type\TypeFactory;

class PhpstanTypeExtractorTest extends TestCase
{
    /**
     * @dataProvider typesDataProvider
     */
    public function testExtractFromProperty(string $expectedType, string $property)
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractFromProperty')->willReturn(new Type('FALLBACK'));

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);
        $reflectionProperty = (new \ReflectionClass(PhpstanExtractableDummy::class))->getProperty($property);

        $this->assertEquals(TypeFactory::createFromString($expectedType), $extractor->extractFromProperty($reflectionProperty));
    }

    public function testCannotHandleIntersectionProperty()
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);
        $reflectionProperty = (new \ReflectionClass(PhpstanExtractableDummy::class))->getProperty('intersection');

        $this->expectException(UnsupportedException::class);

        $extractor->extractFromProperty($reflectionProperty);
    }

    public function testCannotHandleUnknownNode()
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);
        $reflectionProperty = (new \ReflectionClass(PhpstanExtractableDummy::class))->getProperty('unknown');

        $this->expectException(UnsupportedException::class);

        $extractor->extractFromProperty($reflectionProperty);
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testExtractFromFunctionReturn(string $expectedType, string $method)
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractFromFunctionReturn')->willReturn(new Type('FALLBACK'));

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);
        $reflectionMethod = (new \ReflectionClass(PhpstanExtractableDummy::class))->getMethod($method);

        $this->assertEquals(TypeFactory::createFromString($expectedType), $extractor->extractFromFunctionReturn($reflectionMethod));
    }

    public function testFallbackOnVoidAndNeverFunctionReturn()
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractFromFunctionReturn')->willReturn(new Type('FALLBACK'));

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);

        $voidReflectionMethod = (new \ReflectionClass(PhpstanExtractableDummy::class))->getMethod('void');
        $neverReflectionMethod = (new \ReflectionClass(PhpstanExtractableDummy::class))->getMethod('never');

        $this->assertEquals(new Type('FALLBACK'), $extractor->extractFromFunctionReturn($voidReflectionMethod));
        $this->assertEquals(new Type('FALLBACK'), $extractor->extractFromFunctionReturn($neverReflectionMethod));
    }

    public function testExtractClassTypeFromFunctionFunctionReturn()
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractFromFunctionReturn')->willReturn(new Type('FALLBACK'));

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);

        /** @return self */
        $selfReflectionFunction = new \ReflectionFunction(function () {
            return $this;
        });

        $this->assertEquals(TypeFactory::createFromString(self::class), $extractor->extractFromFunctionReturn($selfReflectionFunction));
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testExtractFromParameter(string $expectedType, string $method)
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractFromFunctionParameter')->willReturn(new Type('FALLBACK'));

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);

        $reflectionParameter = (new \ReflectionClass(PhpstanExtractableDummy::class))->getMethod($method)->getParameters()[0];

        $this->assertEquals(TypeFactory::createFromString($expectedType), $extractor->extractFromFunctionParameter($reflectionParameter));
    }

    public function testExtractClassTypeFromParameter()
    {
        $fallbackExtractor = $this->createStub(TypeExtractorInterface::class);
        $fallbackExtractor->method('extractFromFunctionParameter')->willReturn(new Type('FALLBACK'));

        $extractor = new PhpstanTypeExtractor($fallbackExtractor);

        /** @param self $_ */
        $selfReflectionFunction = new \ReflectionFunction(function ($_) {
        });

        $this->assertEquals(TypeFactory::createFromString(self::class), $extractor->extractFromFunctionParameter($selfReflectionFunction->getParameters()[0]));
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: bool}>
     */
    public static function typesDataProvider(): iterable
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
        yield ['iterable', 'iterable'];
        yield ['array', 'nonEmptyArray'];
        yield ['array', 'nonEmptyList'];
        yield ['null', 'null'];
        yield [PhpstanExtractableDummy::class, 'self'];
        yield [PhpstanExtractableDummy::class, 'static'];
        yield [AbstractDummy::class, 'parent'];
        yield ['Symfony\\Component\\Serializer\\Tests\\Fixtures\\Dto\\scoped', 'scoped'];
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

    public function testExtractTemplateFromClass()
    {
        $extractor = new PhpstanTypeExtractor($this->createStub(TypeExtractorInterface::class));

        $this->assertSame(['Tk', 'Tv'], $extractor->extractTemplateFromClass(new \ReflectionClass(PhpstanExtractableDummy::class)));
    }

    public function testExtractTemplateFromClassThrowWhenNonUniqueTemplate()
    {
        $extractor = new PhpstanTypeExtractor($this->createStub(TypeExtractorInterface::class));

        $this->expectException(UnexpectedValueException::class);

        $extractor->extractTemplateFromClass(new \ReflectionClass(NonUniqueTemplatePhpstanExtractableDummy::class));
    }
}
