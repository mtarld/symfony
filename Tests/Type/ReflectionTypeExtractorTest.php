<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\AbstractDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ReflectionExtractableDummy;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;

final class ReflectionTypeExtractorTest extends TestCase
{
    /**
     * @dataProvider typesDataProvider
     */
    public function testExtractFromProperty(string $expectedType, string $property): void
    {
        $reflectionProperty = (new \ReflectionClass(ReflectionExtractableDummy::class))->getProperty($property);

        $this->assertSame($expectedType, (new ReflectionTypeExtractor())->extractFromProperty($reflectionProperty));
    }

    public function testCannotHandleIntersectionProperty(): void
    {
        $reflectionProperty = (new \ReflectionClass(ReflectionExtractableDummy::class))->getProperty('intersection');

        $this->expectException(\LogicException::class);
        $this->expectErrorMessage('Cannot handle intersection types.');

        (new ReflectionTypeExtractor())->extractFromProperty($reflectionProperty);
    }

    public function testThrowIfCannotFindPropertyType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Type of "%s::$undefined" has not been defined.', ReflectionExtractableDummy::class));

        $reflectionProperty = (new \ReflectionClass(ReflectionExtractableDummy::class))->getProperty('undefined');

        (new ReflectionTypeExtractor())->extractFromProperty($reflectionProperty);
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testExtractFromReturnType(string $expectedType, string $method): void
    {
        $reflectionMethod = (new \ReflectionClass(ReflectionExtractableDummy::class))->getMethod($method);

        $this->assertSame($expectedType, (new ReflectionTypeExtractor())->extractFromReturnType($reflectionMethod));
    }

    public function testExtractClassTypeFromFunctionReturnType(): void
    {
        $selfReflectionFunction = new \ReflectionFunction(function (): self {
            return $this;
        });

        $this->assertSame(self::class, (new ReflectionTypeExtractor())->extractFromReturnType($selfReflectionFunction));

        $parentReflectionFunction = new \ReflectionFunction(function (): parent {
            return $this;
        });

        $this->assertSame(parent::class, (new ReflectionTypeExtractor())->extractFromReturnType($parentReflectionFunction));
    }

    public function testCannotHandleIntersectionReturnType(): void
    {
        $reflectionMethod = (new \ReflectionClass(ReflectionExtractableDummy::class))->getMethod('intersection');

        $this->expectException(\LogicException::class);
        $this->expectErrorMessage('Cannot handle intersection types.');

        (new ReflectionTypeExtractor())->extractFromReturnType($reflectionMethod);
    }

    public function testCannotHandleVoidReturnType(): void
    {
        $reflectionMethod = (new \ReflectionClass(ReflectionExtractableDummy::class))->getMethod('void');

        $this->expectException(\LogicException::class);
        $this->expectErrorMessage('Unhandled "void" type.');

        (new ReflectionTypeExtractor())->extractFromReturnType($reflectionMethod);
    }

    public function testCannotHandleNeverReturnType(): void
    {
        $reflectionMethod = (new \ReflectionClass(ReflectionExtractableDummy::class))->getMethod('never');

        $this->expectException(\LogicException::class);
        $this->expectErrorMessage('Unhandled "never" type.');

        (new ReflectionTypeExtractor())->extractFromReturnType($reflectionMethod);
    }

    public function testThrowIfCannotFindFunctionDeclaringClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot find class related to "foo()".');

        $reflectionMethod = $this->createStub(\ReflectionFunction::class);
        $reflectionMethod->method('getName')->willReturn('foo');

        (new ReflectionTypeExtractor())->extractFromReturnType($reflectionMethod);
    }

    public function testThrowIfCannotFindReturnType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Return type of "%s::undefined()" has not been defined.', ReflectionExtractableDummy::class));

        $reflectionMethod = (new \ReflectionClass(ReflectionExtractableDummy::class))->getMethod('undefined');

        (new ReflectionTypeExtractor())->extractFromReturnType($reflectionMethod);
    }

    public function testThrowIfWrongReflectionType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/^Unexpected "[^"]+" type reflection\.$/');

        $reflection = new class() extends \ReflectionType {
        };

        $reflectionMethod = $this->createStub(\ReflectionFunction::class);
        $reflectionMethod->method('getClosureScopeClass')->willReturn($this->createStub(\ReflectionClass::class));
        $reflectionMethod->method('getReturnType')->willReturn($reflection);

        (new ReflectionTypeExtractor())->extractFromReturnType($reflectionMethod);
    }

    /**
     * @dataProvider typesDataProvider
     */
    public function testExtractFromParameter(string $expectedType, string $method): void
    {
        $reflectionParameter = (new \ReflectionClass(ReflectionExtractableDummy::class))->getMethod($method)->getParameters()[0];

        $this->assertSame($expectedType, (new ReflectionTypeExtractor())->extractFromParameter($reflectionParameter));
    }

    public function testThrowIfCannotFindParameterDeclaringClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot find class related to "foo()".');

        $reflectionFunction = $this->createStub(\ReflectionFunctionAbstract::class);
        $reflectionFunction->method('getName')->willReturn('foo');

        $reflectionParameter = $this->createStub(\ReflectionParameter::class);
        $reflectionParameter->method('getDeclaringFunction')->willReturn($reflectionFunction);

        (new ReflectionTypeExtractor())->extractFromParameter($reflectionParameter);
    }

    public function testThrowIfCannotFindParameterType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Type of parameter "_" of "%s::undefined()" has not been defined.', ReflectionExtractableDummy::class));

        $reflectionParameter = (new \ReflectionClass(ReflectionExtractableDummy::class))->getMethod('undefined')->getParameters()[0];

        (new ReflectionTypeExtractor())->extractFromParameter($reflectionParameter);
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
