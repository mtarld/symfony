<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Internal\Parser\Json;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Exception\InvalidTokenException;
use Symfony\Component\Marshaller\Internal\Parser\Json\JsonDictParser;

final class JsonDictParserTest extends TestCase
{
    /**
     * @dataProvider parseDataProvider
     *
     * @param list<string> $expectedKeys
     * @param list<string> $tokens
     */
    public function testParse(array $expectedKeys, array $tokens): void
    {
        $tokens = new \ArrayIterator($tokens);
        $dict = (new JsonDictParser())->parse($tokens, []);

        $keys = [];
        foreach ($dict as $key) {
            $tokens->next();
            $keys[] = $key;
        }

        $this->assertSame($expectedKeys, $keys);
    }

    /**
     * @return iterable<array{0: list<mixed>, 1: list<string>}>
     */
    public function parseDataProvider(): iterable
    {
        yield [[], ['{', '}']];
        yield [['foo'], ['{', '"foo"', ':', '1', '}']];
        yield [['foo', 'bar'], ['{', '"foo"', ':', '1', ',', '"bar"', ':', '2', '}']];
    }

    public function testParseThrowOnInvalidFirstToken(): void
    {
        // do not throw if not iterated
        $value = (new JsonDictParser())->parse(new \ArrayIterator(['foo']), []);
        $this->addToAssertionCount(1);

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Expected "{" token, got "foo".');

        $value = (new JsonDictParser())->parse(new \ArrayIterator(['foo']), []);
        iterator_to_array($value);
    }
}
