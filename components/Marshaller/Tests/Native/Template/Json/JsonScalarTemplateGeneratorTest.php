<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template\Json;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Native\Template\Json\JsonScalarTemplateGenerator;
use Symfony\Component\Marshaller\Native\Type\Type;

final class JsonScalarTemplateGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $nodes = (new JsonScalarTemplateGenerator())->generate(new Type('string'), new VariableNode('accessor'), []);

        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fwrite', [
                new VariableNode('resource'),
                new FunctionNode('\json_encode', [new VariableNode('accessor')]),
            ])),
        ], $nodes);
    }
}
