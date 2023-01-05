<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Parser\Json;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Parser\Json\JsonScalarParser;
use Symfony\Component\Marshaller\Internal\Type\Type;

final class JsonScalarParserTest extends TestCase
{
    /**
     * @param list<string>         $tokens
     * @param array<string, mixed> $context
     *
     * @dataProvider parseDataProvider
     */
    public function testParse(mixed $expectedValue, array $tokens, array $context): void
    {
        $this->assertSame($expectedValue, (new JsonScalarParser())->parse(new \ArrayIterator($tokens), new Type('useless'), $context));
    }

    /**
     * @return iterable<array{0: string, 1: list<string>, 2: array<string, mixed>}>
     */
    public function parseDataProvider(): iterable
    {
        yield [123456789012345678901234567890, ['123456789012345678901234567890'], []];
        yield ['123456789012345678901234567890', ['123456789012345678901234567890'], ['json_decode_flags' => \JSON_BIGINT_AS_STRING]];
        yield [-100.0, ['-1.0e2'], []];
        yield ['foo', ['"foo"'], []];
        yield [true, ['true'], []];
        yield [false, ['false'], []];
        yield [null, ['null'], []];
    }

    public function testSelectNextToken(): void
    {
        $tokens = new \ArrayIterator(['"foo"', 'NEXT_TOKEN']);
        (new JsonScalarParser())->parse($tokens, new Type('useless'), []);

        $this->assertSame('NEXT_TOKEN', $tokens->current());
    }
}
