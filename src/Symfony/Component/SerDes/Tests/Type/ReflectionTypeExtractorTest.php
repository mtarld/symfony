<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Exception\MissingTypeException;
use Symfony\Component\SerDes\Exception\UnsupportedTypeException;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\AbstractDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ReflectionExtractableDummy;
use Symfony\Component\SerDes\Type\ReflectionTypeExtractor;

class ReflectionTypeExtractorTest extends TestCase
{
    /**
     * @dataProvider typesDataProvider
     */
    public function testExtractFromProperty(string $expectedType, string $property)
    {
        $reflectionProperty = (new \ReflectionClass(ReflectionExtractableDummy::class))->getProperty($property);

        $this->assertSame($expectedType, (new ReflectionTypeExtractor())->extractFromProperty($reflectionProperty));
    }

    public function testCannotHandleIntersectionProperty()
    {
        $reflectionProperty = (new \ReflectionClass(ReflectionExtractableDummy::class))->getProperty('intersection');

        $this->expectException(UnsupportedTypeException::class);

        (new ReflectionTypeExtractor())->extractFromProperty($reflectionProperty);
    }

    public function testThrowIfCannotFindPropertyType()
    {
        $reflectionProperty = (new \ReflectionClass(ReflectionExtractableDummy::class))->getProperty('undefined');

        $this->expectException(MissingTypeException::class);

        (new ReflectionTypeExtractor())->extractFromProperty($reflectionProperty);
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testExtractFromFunctionReturn(string $expectedType, string $method)
    {
        $reflectionMethod = (new \ReflectionClass(ReflectionExtractableDummy::class))->getMethod($method);

        $this->assertSame($expectedType, (new ReflectionTypeExtractor())->extractFromFunctionReturn($reflectionMethod));
    }

    public function testExtractClassTypeFromFunctionReturnType()
    {
        $selfReflectionFunction = new \ReflectionFunction(function (): self {
            return $this;
        });

        $this->assertSame(self::class, (new ReflectionTypeExtractor())->extractFromFunctionReturn($selfReflectionFunction));

        $parentReflectionFunction = new \ReflectionFunction(function (): parent {
            return $this;
        });

        $this->assertSame(parent::class, (new ReflectionTypeExtractor())->extractFromFunctionReturn($parentReflectionFunction));
    }

    public function testCannotHandleIntersectionReturnType()
    {
        $reflectionMethod = (new \ReflectionClass(ReflectionExtractableDummy::class))->getMethod('intersection');

        $this->expectException(UnsupportedTypeException::class);

        (new ReflectionTypeExtractor())->extractFromFunctionReturn($reflectionMethod);
    }

    public function testCannotHandleVoidReturnType()
    {
        $reflectionMethod = (new \ReflectionClass(ReflectionExtractableDummy::class))->getMethod('void');

        $this->expectException(UnsupportedTypeException::class);

        (new ReflectionTypeExtractor())->extractFromFunctionReturn($reflectionMethod);
    }

    public function testCannotHandleNeverReturnType()
    {
        $reflectionMethod = (new \ReflectionClass(ReflectionExtractableDummy::class))->getMethod('never');

        $this->expectException(UnsupportedTypeException::class);

        (new ReflectionTypeExtractor())->extractFromFunctionReturn($reflectionMethod);
    }

    public function testThrowIfCannotFindReturnType()
    {
        $this->expectException(MissingTypeException::class);

        $reflectionMethod = (new \ReflectionClass(ReflectionExtractableDummy::class))->getMethod('undefined');

        (new ReflectionTypeExtractor())->extractFromFunctionReturn($reflectionMethod);
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testExtractFromParameter(string $expectedType, string $method)
    {
        $reflectionParameter = (new \ReflectionClass(ReflectionExtractableDummy::class))->getMethod($method)->getParameters()[0];

        $this->assertSame($expectedType, (new ReflectionTypeExtractor())->extractFromFunctionParameter($reflectionParameter));
    }

    public function testThrowIfCannotFindParameterType()
    {
        $this->expectException(MissingTypeException::class);

        $reflectionParameter = (new \ReflectionClass(ReflectionExtractableDummy::class))->getMethod('undefined')->getParameters()[0];

        (new ReflectionTypeExtractor())->extractFromFunctionParameter($reflectionParameter);
    }

    /**
     * @return iterable<array{0: string, 1: string}>
     */
    public function typesDataProvider(): iterable
    {
        yield ['mixed', 'mixed'];
        yield ['int', 'int'];
        yield ['string', 'string'];
        yield ['float', 'float'];
        yield ['bool', 'bool'];
        yield ['array', 'array'];
        yield [ReflectionExtractableDummy::class, 'self'];
        yield [AbstractDummy::class, 'parent'];
        yield [ClassicDummy::class, 'class'];
        yield ['string|int', 'union'];
        yield ['?int', 'nullableBuiltin'];
        yield ['?'.ClassicDummy::class, 'nullableClass'];
        yield ['string|int|null', 'nullableUnion'];
    }
}
