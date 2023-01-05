<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Ast\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Node\TemplateStringNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\VariableNode;

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
     * @return iterable<array{0: string, 1: list<string|VariableNode>}>
     */
    public function compileDataProvider(): iterable
    {
        yield ['""', []];
        yield ['"foobar"', ['foo', 'bar']];
        yield ['"foo{$bar}baz"', ['foo', new VariableNode('bar'), 'baz']];
    }
}
