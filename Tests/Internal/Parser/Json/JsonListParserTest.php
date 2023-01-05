<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Internal\Parser\Json;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Exception\InvalidTokenException;
use Symfony\Component\Marshaller\Internal\Parser\Json\JsonListParser;

final class JsonListParserTest extends TestCase
{
    /**
     * @dataProvider parseDataProvider
     *
     * @param list<string> $tokens
     */
    public function testParse(int $expectedYields, array $tokens): void
    {
        $tokens = new \ArrayIterator($tokens);
        $list = (new JsonListParser())->parse($tokens, []);

        $count = 0;
        foreach ($list as $_) {
            $tokens->next();
            ++$count;
        }

        $this->assertSame($expectedYields, $count);
    }

    /**
     * @return iterable<array{0: int, 1: list<string>}>
     */
    public function parseDataProvider(): iterable
    {
        yield [0, ['[', ']']];
        yield [1, ['[', '1', ']']];
        yield [2, ['[', '1', '2', ']']];
    }

    public function testParseThrowOnInvalidFirstToken(): void
    {
        // do not throw if not iterated
        $value = (new JsonListParser())->parse(new \ArrayIterator(['foo']), []);
        $this->addToAssertionCount(1);

        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Expected "[" token, got "foo".');

        $value = (new JsonListParser())->parse(new \ArrayIterator(['foo']), []);
        iterator_to_array($value);
    }
}
