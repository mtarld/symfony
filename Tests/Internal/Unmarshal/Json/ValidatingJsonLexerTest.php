<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Unmarshal\Json;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Exception\InvalidResourceException;
use Symfony\Component\Marshaller\Internal\Unmarshal\Json\JsonLexer;
use Symfony\Component\Marshaller\Internal\Unmarshal\Json\ValidatingJsonLexer;

final class ValidatingJsonLexerTest extends TestCase
{
    public function testTokens(): void
    {
        /** @var resource $resource */
        $resource = fopen('php://temp', 'w');

        fwrite($resource, '1');
        rewind($resource);

        $this->assertSame([['1', 0]], iterator_to_array((new ValidatingJsonLexer(new JsonLexer()))->tokens($resource, 0, -1, [])));
    }

    /**
     * @dataProvider validJsonTokensDataProvider
     */
    public function testValidJsonTokens(string $file): void
    {
        $lexer = new ValidatingJsonLexer(new JsonLexer());

        iterator_to_array($lexer->tokens(fopen($file, 'r'), 0, -1, []));

        $this->addToAssertionCount(1);
    }

    /**
     * Pulled from https://github.com/nst/JSONTestSuite.
     *
     * @return iterable<array{0: string}>
     */
    public function validJsonTokensDataProvider(): iterable
    {
        foreach (glob(\dirname(__DIR__, 3).'/Fixtures/Resources/json/valid/*') as $file) {
            yield [$file];
        }
    }

    /**
     * @dataProvider invalidJsonTokensDataProvider
     */
    public function testInvalidJsonTokens(string $file): void
    {
        $this->expectException(InvalidResourceException::class);

        iterator_to_array((new ValidatingJsonLexer(new JsonLexer()))->tokens(fopen($file, 'r'), 0, -1, []));
    }

    /**
     * Pulled from https://github.com/nst/JSONTestSuite.
     *
     * @return iterable<array{0: string}>
     */
    public function invalidJsonTokensDataProvider(): iterable
    {
        foreach (glob(\dirname(__DIR__, 3).'/Fixtures/Resources/json/invalid/*') as $file) {
            yield [$file];
        }
    }

    /**
     * @return resource
     */
    private function createResource(string $content): mixed
    {
        return $resource;
    }
}
