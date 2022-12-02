<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Ast\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;

final class ScalarNodeTest extends TestCase
{
    /**
     * @dataProvider compileDataProvider
     */
    public function testCompile(string $expectedSource, mixed $scalar): void
    {
        (new ScalarNode($scalar))->compile($compiler = new Compiler());
        $this->assertSame($expectedSource, $compiler->source());
    }

    /**
     * @return iterable<string, mixed>
     */
    public function compileDataProvider(): iterable
    {
        yield ['null', null];
        yield ['123', 123];
        yield ['123.456', 123.456];
        yield ['true', true];
        yield ['false', false];
        yield ['"string"', 'string'];
        yield ['"\"string\""', '"string"'];
        yield ['"str\\\\ing"', 'str\ing'];
    }
}
