<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Parser\Json;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Exception\UnexpectedTokenException;
use Symfony\Component\Marshaller\Internal\Parser\Json\JsonListParser;

final class JsonListParserTest extends TestCase
{
    // /**
    //  * @dataProvider parseDataProvider
    //  *
    //  * @param list<string> $tokens
    //  */
    // public function testParse(int $expectedYields, array $tokens): void
    // {
    //     $tokens = new \ArrayIterator($tokens);
    //     $list = (new JsonListParser())->parse($tokens, []);
    //
    //     $count = 0;
    //     foreach ($list as $_) {
    //         $tokens->next();
    //         ++$count;
    //     }
    //
    //     $this->assertSame($expectedYields, $count);
    // }
    //
    // /**
    //  * @return iterable<array{0: int, 1: list<string>}>
    //  */
    // public function parseDataProvider(): iterable
    // {
    //     yield [0, ['[', ']']];
    //     yield [1, ['[', '1', ']']];
    //     yield [2, ['[', '1', '2', ']']];
    // }
    //
    // public function testParseThrowOnInvalidFirstToken(): void
    // {
    //     // do not throw if not iterated
    //     $value = (new JsonListParser())->parse(new \ArrayIterator(['foo']), []);
    //     $this->addToAssertionCount(1);
    //
    //     $this->expectException(UnexpectedTokenException::class);
    //     $this->expectExceptionMessage('Expected "[" token, got "foo".');
    //
    //     $value = (new JsonListParser())->parse(new \ArrayIterator(['foo']), []);
    //     iterator_to_array($value);
    // }
}
