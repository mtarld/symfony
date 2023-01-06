<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Parser;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Parser\DictParserInterface;
use Symfony\Component\Marshaller\Internal\Parser\ListParserInterface;
use Symfony\Component\Marshaller\Internal\Parser\NullableParserInterface;
use Symfony\Component\Marshaller\Internal\Parser\Parser;
use Symfony\Component\Marshaller\Internal\Parser\ScalarParserInterface;
use Symfony\Component\Marshaller\Internal\Type\Type;
use Symfony\Component\Marshaller\Internal\Type\UnionType;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithConstructorWithDefaultValues;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithConstructorWithNullableValues;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithConstructorWithRequiredValues;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\DummyWithPrivateConstructor;

final class ParserTest extends TestCase
{
    public function testParseUnion(): void
    {
        $tokens = new \ArrayIterator();

        $scalarParser = $this->createMock(ScalarParserInterface::class);
        $scalarParser
            ->expects($this->once())
            ->method('parse')
            ->with($tokens)
            ->willReturn('SCALAR');

        $listParser = $this->createMock(ListParserInterface::class);
        $listParser
            ->expects($this->never())
            ->method('parse');

        $parser = $this->createParser(scalarParser: $scalarParser, listParser: $listParser);
        $value = $parser->parse(new \ArrayIterator(), new UnionType([new Type('int'), Type::createFromString('array<string, string>')]), [
            'union_selector' => [
                'int|array<string, string>' => 'int',
            ],
        ]);

        $this->assertSame('SCALAR', $value);
    }

    public function testCannotParUnionWithoutSelector(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot guess type to use for "int|string", you may specify a type in "$context[\'union_selector\'][\'int|string\']".');

        $this->createParser()->parse(new \ArrayIterator(), new UnionType([new Type('int'), new Type('string')]), []);
    }

    public function testParseNullNullable(): void
    {
        $tokens = new \ArrayIterator();

        $nullableParser = $this->createMock(NullableParserInterface::class);
        $nullableParser
            ->expects($this->once())
            ->method('parse')
            ->with($tokens)
            ->willReturn(null);

        $scalarParser = $this->createMock(ScalarParserInterface::class);
        $scalarParser
            ->expects($this->never())
            ->method('parse');

        $parser = $this->createParser(nullableParser: $nullableParser, scalarParser: $scalarParser);
        $value = $parser->parse($tokens, new Type('string', isNullable: true), []);

        $this->assertNull($value);
    }

    public function testParseNotNullNullable(): void
    {
        $tokens = new \ArrayIterator();

        $nullableParser = $this->createStub(NullableParserInterface::class);
        $nullableParser->method('parse')->willReturnCallback(static function (\Iterator $tokens, callable $handle) {
            return $handle($tokens);
        });

        $scalarParser = $this->createMock(ScalarParserInterface::class);
        $scalarParser
            ->expects($this->once())
            ->method('parse')
            ->with($tokens, new Type('string'))
            ->willReturn('SCALAR');

        $parser = $this->createParser(nullableParser: $nullableParser, scalarParser: $scalarParser);
        $value = $parser->parse($tokens, new Type('string', isNullable: true), []);

        $this->assertSame('SCALAR', $value);
    }

    public function testParseScalar(): void
    {
        $tokens = new \ArrayIterator();

        $scalarParser = $this->createMock(ScalarParserInterface::class);
        $scalarParser
            ->expects($this->once())
            ->method('parse')
            ->with($tokens, new Type('string'))
            ->willReturn('SCALAR');

        $parser = $this->createParser(scalarParser: $scalarParser);
        $value = $parser->parse($tokens, new Type('string'), []);

        $this->assertSame('SCALAR', $value);
    }

    public function testParseList(): void
    {
        $tokens = new \ArrayIterator();

        $scalarParser = $this->createMock(ScalarParserInterface::class);
        $scalarParser
            ->expects($this->exactly(2))
            ->method('parse')
            ->with($tokens, new Type('string'))
            ->willReturn('SCALAR');

        $listParser = $this->createMock(ListParserInterface::class);
        $listParser
            ->expects($this->once())
            ->method('parse')
            ->with($tokens)
            ->willReturn(new \ArrayIterator([null, null]));

        $parser = $this->createParser(scalarParser: $scalarParser, listParser: $listParser);
        $value = $parser->parse($tokens, Type::createFromString('array<int, string>'), []);

        $this->assertSame(['SCALAR', 'SCALAR'], $value);
    }

    public function testParseNestedList(): void
    {
        $tokens = new \ArrayIterator();

        $scalarParser = $this->createMock(ScalarParserInterface::class);
        $scalarParser
            ->expects($this->once())
            ->method('parse')
            ->with($tokens, new Type('string'))
            ->willReturn('SCALAR');

        $listParser = $this->createMock(ListParserInterface::class);
        $listParser
            ->expects($this->exactly(2))
            ->method('parse')
            ->with($tokens)
            ->willReturn(new \ArrayIterator([null]));

        $parser = $this->createParser(scalarParser: $scalarParser, listParser: $listParser);
        $value = $parser->parse($tokens, Type::createFromString('array<int, array<int, string>>'), []);

        $this->assertSame([['SCALAR']], $value);
    }

    public function testParseIterableList(): void
    {
        $tokens = new \ArrayIterator();

        $scalarParser = $this->createMock(ScalarParserInterface::class);
        $scalarParser
            ->expects($this->exactly(2))
            ->method('parse')
            ->with($tokens, new Type('string'))
            ->willReturn('SCALAR');

        $listParser = $this->createMock(ListParserInterface::class);
        $listParser
            ->expects($this->once())
            ->method('parse')
            ->with($tokens)
            ->willReturn(new \ArrayIterator([null, null]));

        $parser = $this->createParser(scalarParser: $scalarParser, listParser: $listParser);
        $value = $parser->parse($tokens, Type::createFromString('iterable<int, string>'), []);

        $this->assertInstanceOf(\Generator::class, $value);
        $this->assertSame(['SCALAR', 'SCALAR'], iterator_to_array($value));
    }

    public function testParseNestedIterableList(): void
    {
        $tokens = new \ArrayIterator();

        $scalarParser = $this->createMock(ScalarParserInterface::class);
        $scalarParser
            ->expects($this->once())
            ->method('parse')
            ->with($tokens, new Type('string'))
            ->willReturn('SCALAR');

        $listParser = $this->createMock(ListParserInterface::class);
        $listParser
            ->expects($this->exactly(2))
            ->method('parse')
            ->with($tokens)
            ->willReturn(new \ArrayIterator([null]));

        $parser = $this->createParser(scalarParser: $scalarParser, listParser: $listParser);
        $value = $parser->parse($tokens, Type::createFromString('iterable<int, iterable<int, string>>'), []);

        $this->assertInstanceOf(\Generator::class, $value);

        $result = [];
        foreach ($value as $v) {
            $result[] = iterator_to_array($v);
        }

        $this->assertSame([['SCALAR']], $result);
    }

    public function testParseDict(): void
    {
        $tokens = new \ArrayIterator();

        $scalarParser = $this->createMock(ScalarParserInterface::class);
        $scalarParser
            ->expects($this->exactly(2))
            ->method('parse')
            ->with($tokens, new Type('string'))
            ->willReturn('SCALAR');

        $dictParser = $this->createMock(DictParserInterface::class);
        $dictParser
            ->expects($this->once())
            ->method('parse')
            ->with($tokens)
            ->willReturn(new \ArrayIterator(['foo', 'bar']));

        $parser = $this->createParser(scalarParser: $scalarParser, dictParser: $dictParser);
        $value = $parser->parse($tokens, Type::createFromString('array<string, string>'), []);

        $this->assertSame(['foo' => 'SCALAR', 'bar' => 'SCALAR'], $value);
    }

    public function testParseNestedDict(): void
    {
        $tokens = new \ArrayIterator();

        $scalarParser = $this->createMock(ScalarParserInterface::class);
        $scalarParser
            ->expects($this->once())
            ->method('parse')
            ->with($tokens, new Type('string'))
            ->willReturn('SCALAR');

        $dictParser = $this->createMock(DictParserInterface::class);
        $dictParser
            ->expects($this->exactly(2))
            ->method('parse')
            ->with($tokens)
            ->willReturn(new \ArrayIterator(['foo']));

        $parser = $this->createParser(scalarParser: $scalarParser, dictParser: $dictParser);
        $value = $parser->parse($tokens, Type::createFromString('array<string, array<string, string>>'), []);

        $this->assertSame(['foo' => ['foo' => 'SCALAR']], $value);
    }

    public function testParseIterableDict(): void
    {
        $tokens = new \ArrayIterator();

        $scalarParser = $this->createMock(ScalarParserInterface::class);
        $scalarParser
            ->expects($this->exactly(2))
            ->method('parse')
            ->with($tokens, new Type('string'))
            ->willReturn('SCALAR');

        $dictParser = $this->createMock(DictParserInterface::class);
        $dictParser
            ->expects($this->once())
            ->method('parse')
            ->with($tokens)
            ->willReturn(new \ArrayIterator(['foo', 'bar']));

        $parser = $this->createParser(scalarParser: $scalarParser, dictParser: $dictParser);
        $value = $parser->parse($tokens, Type::createFromString('iterable<string, string>'), []);

        $this->assertInstanceOf(\Generator::class, $value);
        $this->assertSame(['foo' => 'SCALAR', 'bar' => 'SCALAR'], iterator_to_array($value));
    }

    public function testParseNestedIterableDict(): void
    {
        $tokens = new \ArrayIterator();

        $scalarParser = $this->createMock(ScalarParserInterface::class);
        $scalarParser
            ->expects($this->once())
            ->method('parse')
            ->with($tokens, new Type('string'))
            ->willReturn('SCALAR');

        $dictParser = $this->createMock(DictParserInterface::class);
        $dictParser
            ->expects($this->exactly(2))
            ->method('parse')
            ->with($tokens)
            ->willReturn(new \ArrayIterator(['foo']));

        $parser = $this->createParser(scalarParser: $scalarParser, dictParser: $dictParser);
        $value = $parser->parse($tokens, Type::createFromString('iterable<string, iterable<string, string>>'), []);

        $this->assertInstanceOf(\Generator::class, $value);

        $result = [];
        foreach ($value as $k => $v) {
            $result[$k] = iterator_to_array($v);
        }

        $this->assertSame(['foo' => ['foo' => 'SCALAR']], $result);
    }

    public function testParseObject(): void
    {
        $object = new class() {
            public string $foo = 'default';
            public string $bar = 'default';
            public string $baz = 'default';
        };

        $tokens = new \ArrayIterator();

        $scalarParser = $this->createMock(ScalarParserInterface::class);
        $scalarParser
            ->expects($this->exactly(2))
            ->method('parse')
            ->with($tokens, new Type('string'))
            ->willReturn('SCALAR');

        $dictParser = $this->createMock(DictParserInterface::class);
        $dictParser
            ->expects($this->once())
            ->method('parse')
            ->with($tokens)
            ->willReturn(new \ArrayIterator(['foo', 'bar']));

        $parser = $this->createParser(scalarParser: $scalarParser, dictParser: $dictParser);
        $value = $parser->parse($tokens, Type::createFromString($object::class), []);

        $expectedObject = clone $object;
        $expectedObject->foo = 'SCALAR';
        $expectedObject->bar = 'SCALAR';

        $this->assertEquals($expectedObject, $value);
    }

    public function testParseObjectWithHooks(): void
    {
        $object = new class() {
            public string $foo = 'default';
        };

        $parsedValue = null;
        $tokens = new \ArrayIterator();

        $scalarParser = $this->createMock(ScalarParserInterface::class);
        $scalarParser
            ->expects($this->once())
            ->method('parse')
            ->with($tokens, new Type('string'))
            ->willReturn('SCALAR');

        $dictParser = $this->createMock(DictParserInterface::class);
        $dictParser
            ->expects($this->once())
            ->method('parse')
            ->with($tokens)
            ->willReturn(new \ArrayIterator(['fooAlias']));

        $parser = $this->createParser(scalarParser: $scalarParser, dictParser: $dictParser);
        $value = $parser->parse($tokens, Type::createFromString($object::class), [
            'hooks' => [
                $object::class => [
                    'fooAlias' => static function (\ReflectionClass $reflection, object $object, callable $value, array $context) use (&$parsedValue): void {
                        $parsedValue = $value('string', $context);
                        $object->foo = 'HOOK_VALUE';
                    },
                ],
            ],
        ]);

        $expectedObject = clone $object;
        $expectedObject->foo = 'HOOK_VALUE';

        $this->assertSame('SCALAR', $parsedValue);
        $this->assertEquals($expectedObject, $value);
    }

    public function testInstantiateObjectWithoutConstructor(): void
    {
        $value = $this->createParser()->parse(new \ArrayIterator(), Type::createFromString(ClassicDummy::class), []);

        $this->assertEquals(new ClassicDummy(), $value);
    }

    public function testInstantiateObjectWithPrivateConstructor(): void
    {
        $value = $this->createParser()->parse(new \ArrayIterator(), Type::createFromString(DummyWithPrivateConstructor::class), []);

        $this->assertInstanceOf(DummyWithPrivateConstructor::class, $value);
        $this->assertSame(1, $value->id);
    }

    public function testInstantiateObjectWithConstructorWithDefaultValues(): void
    {
        $value = $this->createParser()->parse(new \ArrayIterator(), Type::createFromString(DummyWithConstructorWithDefaultValues::class), []);

        $this->assertInstanceOf(DummyWithConstructorWithDefaultValues::class, $value);
        $this->assertSame(1, $value->id);
    }

    public function testInstantiateObjectWithConstructorWithNullableValues(): void
    {
        $value = $this->createParser()->parse(new \ArrayIterator(), Type::createFromString(DummyWithConstructorWithNullableValues::class), []);

        $this->assertInstanceOf(DummyWithConstructorWithNullableValues::class, $value);
        $this->assertNull($value->id);
    }

    public function testInstantiateObjectWithConstructorWithRequiredValues(): void
    {
        $value = $this->createParser()->parse(new \ArrayIterator(), Type::createFromString(DummyWithConstructorWithRequiredValues::class), []);

        $this->assertInstanceOf(DummyWithConstructorWithRequiredValues::class, $value);
        $this->assertSame(1, $value->id);
    }

    private function createParser(
        NullableParserInterface $nullableParser = null,
        ScalarParserInterface $scalarParser = null,
        ListParserInterface $listParser = null,
        DictParserInterface $dictParser = null,
    ): Parser {
        if (null === $nullableParser) {
            $nullableParser = $this->createStub(NullableParserInterface::class);
            $nullableParser->method('parse')->willReturn(null);
        }

        if (null === $scalarParser) {
            $scalarParser = $this->createStub(ScalarParserInterface::class);
            $scalarParser->method('parse')->willReturn('SCALAR');
        }

        if (null === $listParser) {
            $listParser = $this->createStub(ListParserInterface::class);
            $listParser->method('parse')->willReturn(new \ArrayIterator());
        }

        if (null === $dictParser) {
            $dictParser = $this->createStub(DictParserInterface::class);
            $dictParser->method('parse')->willReturn(new \ArrayIterator());
        }

        return new Parser($nullableParser, $scalarParser, $listParser, $dictParser);
    }
}
