<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Ast\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Node\TemplateStringNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;

final class TemplateStringNodeTest extends TestCase
{
    /**
     * @param list<string|VariableNode> $parts
     *
     * @dataProvider compileDataProvider
     */
    public function testCompile(string $expectedSource, array $parts): void
    {
        (new TemplateStringNode(...$parts))->compile($compiler = new Compiler());
        $this->assertSame($expectedSource, $compiler->source());
    }

    /**
     * @return iterable<string, list<string|VariableNode>>
     */
    public function compileDataProvider(): iterable
    {
        yield ['""', []];
        yield ['"foobar"', ['foo', 'bar']];
        yield ['"foo{$bar}baz"', ['foo', new VariableNode('bar'), 'baz']];
    }
}
