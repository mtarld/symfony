<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Internal\Serialize\TemplateGenerator;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Internal\Serialize\Node\ArrayAccessNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\AssignNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\BinaryNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ExpressionNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ForEachNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\FunctionNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\PropertyNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ScalarNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\TemplateStringNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\VariableNode;
use Symfony\Component\SerDes\Internal\Serialize\TemplateGenerator\JsonTemplateGenerator;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\SerDes\Type\ReflectionTypeExtractor;
use Symfony\Component\SerDes\Type\TypeFactory;
use Symfony\Component\SerDes\Type\TypeSorter;

class JsonTemplateGeneratorTest extends TestCase
{
    private readonly JsonTemplateGenerator $templateGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templateGenerator = new JsonTemplateGenerator(new ReflectionTypeExtractor(), new TypeSorter());
    }

    public function testGenerateNull()
    {
        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('null')])),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('null'), new VariableNode('accessor'), []));
    }

    public function testGenerateScalar()
    {
        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('\json_encode', [
                new VariableNode('accessor'),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('json_encode_flags')), new ScalarNode(0)),
            ])])),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('int'), new VariableNode('accessor'), []));
    }

    public function testGenerateList()
    {
        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('[')])),
            new ExpressionNode(new AssignNode(new VariableNode('prefix_0'), new ScalarNode(''))),
            new ForEachNode(new VariableNode('accessor'), null, 'value_0', [
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new VariableNode('prefix_0')])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('\json_encode', [
                    new VariableNode('value_0'),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('json_encode_flags')), new ScalarNode(0)),
                ])])),
                new ExpressionNode(new AssignNode(new VariableNode('prefix_0'), new ScalarNode(','))),
            ]),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode(']')])),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('array<int, int>'), new VariableNode('accessor'), []));
    }

    public function testGenerateDict()
    {
        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('{')])),
            new ExpressionNode(new AssignNode(new VariableNode('prefix_0'), new ScalarNode(''))),
            new ForEachNode(new VariableNode('accessor'), 'key_0', 'value_0', [
                new ExpressionNode(new AssignNode(
                    new VariableNode('key_0'),
                    new FunctionNode('\substr', [
                        new FunctionNode('\json_encode', [
                            new VariableNode('key_0'),
                            new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('json_encode_flags')), new ScalarNode(0)),
                        ]),
                        new ScalarNode(1),
                        new ScalarNode(-1),
                    ]),
                )),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new TemplateStringNode(
                    new VariableNode('prefix_0'),
                    '"',
                    new VariableNode('key_0'),
                    '":',
                )])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('\json_encode', [
                    new VariableNode('value_0'),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('json_encode_flags')), new ScalarNode(0)),
                ])])),
                new ExpressionNode(new AssignNode(new VariableNode('prefix_0'), new ScalarNode(','))),
            ]),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('}')])),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('array<string, int>'), new VariableNode('accessor'), []));
    }

    public function testGenerateObject()
    {
        $this->assertEquals([
            new ExpressionNode(new AssignNode(new VariableNode('object_0'), new VariableNode('accessor'))),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('{')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('"')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('id')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('":')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('\json_encode', [
                new PropertyNode(new VariableNode('object_0'), 'id'),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('json_encode_flags')), new ScalarNode(0)),
            ])])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode(',')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('"')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('name')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('":')])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('\json_encode', [
                new PropertyNode(new VariableNode('object_0'), 'name'),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('json_encode_flags')), new ScalarNode(0)),
            ])])),
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('}')])),
        ], $this->templateGenerator->generate(TypeFactory::createFromString(ClassicDummy::class), new VariableNode('accessor'), []));
    }

    public function testGenerateMixed()
    {
        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new FunctionNode('\json_encode', [
                new VariableNode('accessor'),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('json_encode_flags')), new ScalarNode(0)),
            ])])),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('mixed'), new VariableNode('accessor'), []));
    }
}
