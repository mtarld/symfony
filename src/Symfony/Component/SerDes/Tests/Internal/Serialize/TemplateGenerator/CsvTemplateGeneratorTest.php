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
use Symfony\Component\SerDes\Exception\UnexpectedValueException;
use Symfony\Component\SerDes\Internal\Serialize\Node\ArgumentsNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ArrayAccessNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ArrayNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\AssignNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\BinaryNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\CastNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ClosureNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ExpressionNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ForEachNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\FunctionNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\IfNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\NewNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\PropertyNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ReturnNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ScalarNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ThrowNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\UnaryNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\VariableNode;
use Symfony\Component\SerDes\Internal\Serialize\TemplateGenerator\CsvTemplateGenerator;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\SerDes\Type\ReflectionTypeExtractor;
use Symfony\Component\SerDes\Type\TypeFactory;
use Symfony\Component\SerDes\Type\TypeSorter;

class CsvTemplateGeneratorTest extends TestCase
{
    private readonly CsvTemplateGenerator $templateGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templateGenerator = new CsvTemplateGenerator(new ReflectionTypeExtractor(), new TypeSorter());
    }

    public function testGenerateNullList()
    {
        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fputcsv', [
                new VariableNode('resource'),
                new ArrayNode([new ScalarNode(0)]),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                new ScalarNode(''),
            ])),
            new ExpressionNode(new FunctionNode('\fwrite', [
                new VariableNode('resource'),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
            ])),
            new ForEachNode(new VariableNode('accessor'), null, 'row_0', [
                new ExpressionNode(new FunctionNode('\fputcsv', [
                    new VariableNode('resource'),
                    new ArrayNode([new ScalarNode(null)]),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                    new ScalarNode(''),
                ])),
                new ExpressionNode(new FunctionNode('\fwrite', [
                    new VariableNode('resource'),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                ])),
            ]),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('array<int, null>'), new VariableNode('accessor'), []));
    }

    public function testGenerateScalarList()
    {
        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fputcsv', [
                new VariableNode('resource'),
                new ArrayNode([new ScalarNode(0)]),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                new ScalarNode(''),
            ])),
            new ExpressionNode(new FunctionNode('\fwrite', [
                new VariableNode('resource'),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
            ])),
            new ForEachNode(new VariableNode('accessor'), null, 'row_0', [
                new ExpressionNode(new FunctionNode('\fputcsv', [
                    new VariableNode('resource'),
                    new ArrayNode([new VariableNode('row_0')]),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                    new ScalarNode(''),
                ])),
                new ExpressionNode(new FunctionNode('\fwrite', [
                    new VariableNode('resource'),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                ])),
            ]),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('array<int, int>'), new VariableNode('accessor'), []));
    }

    public function testGenerateEnumList()
    {
        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fputcsv', [
                new VariableNode('resource'),
                new ArrayNode([new ScalarNode(0)]),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                new ScalarNode(''),
            ])),
            new ExpressionNode(new FunctionNode('\fwrite', [
                new VariableNode('resource'),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
            ])),
            new ForEachNode(new VariableNode('accessor'), null, 'row_0', [
                new ExpressionNode(new FunctionNode('\fputcsv', [
                    new VariableNode('resource'),
                    new ArrayNode([new PropertyNode(new VariableNode('row_0'), 'value')]),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                    new ScalarNode(''),
                ])),
                new ExpressionNode(new FunctionNode('\fwrite', [
                    new VariableNode('resource'),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                ])),
            ]),
        ], $this->templateGenerator->generate(TypeFactory::createFromString(sprintf('array<int, %s>', DummyBackedEnum::class)), new VariableNode('accessor'), []));
    }

    public function testGenerateListList()
    {
        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fputcsv', [
                new VariableNode('resource'),
                new FunctionNode('\array_keys', [new FunctionNode('\reset', [new VariableNode('accessor')])]),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                new ScalarNode(''),
            ])),
            new ExpressionNode(new FunctionNode('\fwrite', [
                new VariableNode('resource'),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
            ])),
            new ForEachNode(new VariableNode('accessor'), null, 'row_0', [
                new ExpressionNode(new FunctionNode('\fputcsv', [
                    new VariableNode('resource'),
                    new VariableNode('row_0'),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                    new ScalarNode(''),
                ])),
                new ExpressionNode(new FunctionNode('\fwrite', [
                    new VariableNode('resource'),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                ])),
            ]),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('array<int, array<int, int>>'), new VariableNode('accessor'), []));
    }

    public function testGenerateDictList()
    {
        $this->assertEquals([
            new ExpressionNode(new AssignNode(
                new VariableNode('headers_0'),
                new FunctionNode('\array_reduce', [
                    new VariableNode('accessor'),
                    new ClosureNode(
                        new ArgumentsNode(['c' => 'array', 'i' => 'array']),
                        'array',
                        true,
                        [
                            new ExpressionNode(new ReturnNode(new FunctionNode('\array_values', [
                                new FunctionNode('\array_unique', [
                                    new FunctionNode('\array_merge', [new VariableNode('c'), new FunctionNode('\array_keys', [new VariableNode('i')])]),
                                ]),
                            ]))),
                        ],
                    ),
                    new ArrayNode([]),
                ]),
            )),
            new ExpressionNode(new AssignNode(new VariableNode('flippedHeaders_0'), new FunctionNode('\array_fill_keys', [new VariableNode('headers_0'), new ScalarNode('')]))),
            new ExpressionNode(new FunctionNode('\fputcsv', [
                new VariableNode('resource'),
                new VariableNode('headers_0'),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                new ScalarNode(''),
            ])),
            new ExpressionNode(new FunctionNode('\fwrite', [
                new VariableNode('resource'),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
            ])),
            new ForEachNode(new VariableNode('accessor'), null, 'row_0', [
                new ExpressionNode(new FunctionNode('\fputcsv', [
                    new VariableNode('resource'),
                    new FunctionNode('\array_replace', [new VariableNode('flippedHeaders_0'), new VariableNode('row_0')]),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                    new ScalarNode(''),
                ])),
                new ExpressionNode(new FunctionNode('\fwrite', [
                    new VariableNode('resource'),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                ])),
            ]),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('array<int, array<string, int>>'), new VariableNode('accessor'), []));
    }

    public function testGenerateObjectList()
    {
        $this->assertEquals([
            new ExpressionNode(new AssignNode(
                new VariableNode('headers_0'),
                new ArrayNode([new ScalarNode('id'), new ScalarNode('name')]),
            )),
            new ExpressionNode(new AssignNode(
                new VariableNode('flippedHeaders_0'),
                new FunctionNode('\array_fill_keys', [new VariableNode('headers_0'), new ScalarNode('')]),
            )),
            new ExpressionNode(new FunctionNode('\fputcsv', [
                new VariableNode('resource'),
                new VariableNode('headers_0'),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                new ScalarNode(''),
            ])),
            new ExpressionNode(new FunctionNode('\fwrite', [
                new VariableNode('resource'),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
            ])),
            new ForEachNode(new VariableNode('accessor'), null, 'row_0', [
                new ExpressionNode(new AssignNode(new VariableNode('object_0'), new VariableNode('row_0'))),
                new ExpressionNode(new FunctionNode('\fputcsv', [
                    new VariableNode('resource'),
                    new FunctionNode('\array_replace', [
                        new VariableNode('flippedHeaders_0'),
                        new ArrayNode(['id' => new PropertyNode(new VariableNode('object_0'), 'id'), 'name' => new PropertyNode(new VariableNode('object_0'), 'name')]),
                    ]),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                    new ScalarNode(''),
                ])),
                new ExpressionNode(new FunctionNode('\fwrite', [
                    new VariableNode('resource'),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                ])),
            ]),
        ], $this->templateGenerator->generate(TypeFactory::createFromString(sprintf('array<int, %s>', ClassicDummy::class)), new VariableNode('accessor'), []));
    }

    public function testGenerateRawObjectList()
    {
        $this->assertEquals([
            new ExpressionNode(new AssignNode(
                new VariableNode('headers_0'),
                new FunctionNode('\array_keys', [new CastNode('array', new FunctionNode('\reset', [new VariableNode('accessor')]))]),
            )),
            new ExpressionNode(new AssignNode(
                new VariableNode('flippedHeaders_0'),
                new FunctionNode('\array_fill_keys', [new VariableNode('headers_0'), new ScalarNode('')]),
            )),
            new ExpressionNode(new FunctionNode('\fputcsv', [
                new VariableNode('resource'),
                new VariableNode('headers_0'),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                new ScalarNode(''),
            ])),
            new ExpressionNode(new FunctionNode('\fwrite', [
                new VariableNode('resource'),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
            ])),
            new ForEachNode(new VariableNode('accessor'), null, 'row_0', [
                new IfNode(
                    new FunctionNode('\is_iterable', [new VariableNode('row_0')]),
                    [
                        new ExpressionNode(new FunctionNode('\fputcsv', [
                            new VariableNode('resource'),
                            new FunctionNode('\array_replace', [new VariableNode('flippedHeaders_0'), new VariableNode('row_0')]),
                            new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                            new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                            new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                            new ScalarNode(''),
                        ])),
                    ],
                    [
                        new ExpressionNode(new FunctionNode('\fputcsv', [
                            new VariableNode('resource'),
                            new ArrayNode([new VariableNode('row_0')]),
                            new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                            new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                            new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                            new ScalarNode(''),
                        ])),
                    ],
                    [
                        [
                            'condition' => new BinaryNode(
                                '&&',
                                new FunctionNode('\is_object', [new VariableNode('row_0')]),
                                new FunctionNode('\is_subclass_of', [new FunctionNode('\get_class', [new VariableNode('row_0')]), new ScalarNode(\BackedEnum::class)])
                            ),
                            'body' => [
                                new ExpressionNode(new FunctionNode('\fputcsv', [
                                    new VariableNode('resource'),
                                    new ArrayNode([new PropertyNode(new VariableNode('row_0'), 'value')]),
                                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                                    new ScalarNode(''),
                                ])),
                            ],
                        ],
                        [
                            'condition' => new FunctionNode('\is_object', [new VariableNode('row_0')]),
                            'body' => [
                                new ExpressionNode(new FunctionNode('\fputcsv', [
                                    new VariableNode('resource'),
                                    new CastNode('array', new VariableNode('row_0')),
                                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                                    new ScalarNode(''),
                                ])),
                            ],
                        ],
                    ],
                ),
                new ExpressionNode(new FunctionNode('\fwrite', [
                    new VariableNode('resource'),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                ])),
            ]),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('array<int, object>'), new VariableNode('accessor'), []));
    }

    public function testGenerateMixed()
    {
        $this->assertEquals([
            new IfNode(new UnaryNode('!', new FunctionNode('\is_iterable', [new VariableNode('accessor')])), [
                new ExpressionNode(new ThrowNode(new NewNode('\\'.UnexpectedValueException::class, [new FunctionNode('\sprintf', [
                    new ScalarNode('Expecting first level data type to be a list, but got "%s".'),
                    new FunctionNode('\get_debug_type', [new VariableNode('accessor')]),
                ])]))),
            ]),
            new IfNode(
                new FunctionNode('\is_iterable', [new FunctionNode('\reset', [new VariableNode('accessor')])]),
                [
                    new ExpressionNode(new AssignNode(
                        new VariableNode('headers_0'),
                        new FunctionNode('\array_reduce', [
                            new VariableNode('accessor'),
                            new ClosureNode(
                                new ArgumentsNode(['c' => 'array', 'i' => 'array']),
                                'array',
                                true,
                                [
                                    new ExpressionNode(new ReturnNode(new FunctionNode('\array_values', [
                                        new FunctionNode('\array_unique', [
                                            new FunctionNode('\array_merge', [new VariableNode('c'), new FunctionNode('\array_keys', [new VariableNode('i')])]),
                                        ]),
                                    ]))),
                                ],
                            ),
                            new ArrayNode([]),
                        ]),
                    )),
                    new ExpressionNode(new AssignNode(new VariableNode('flippedHeaders_0'), new FunctionNode('\array_fill_keys', [new VariableNode('headers_0'), new ScalarNode('')]))),
                    new ExpressionNode(new FunctionNode('\fputcsv', [
                        new VariableNode('resource'),
                        new VariableNode('headers_0'),
                        new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                        new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                        new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                        new ScalarNode(''),
                    ])),
                    new ExpressionNode(new FunctionNode('\fwrite', [
                        new VariableNode('resource'),
                        new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                    ])),
                    new ForEachNode(new VariableNode('accessor'), null, 'row_0', [
                        new ExpressionNode(new FunctionNode('\fputcsv', [
                            new VariableNode('resource'),
                            new FunctionNode('\array_replace', [new VariableNode('flippedHeaders_0'), new VariableNode('row_0')]),
                            new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                            new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                            new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                            new ScalarNode(''),
                        ])),
                        new ExpressionNode(new FunctionNode('\fwrite', [
                            new VariableNode('resource'),
                            new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                        ])),
                    ]),
                ],
                [
                    new ExpressionNode(new FunctionNode('\fputcsv', [
                        new VariableNode('resource'),
                        new ArrayNode([new ScalarNode(0)]),
                        new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                        new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                        new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                        new ScalarNode(''),
                    ])),
                    new ExpressionNode(new FunctionNode('\fwrite', [
                        new VariableNode('resource'),
                        new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                    ])),
                    new ForEachNode(new VariableNode('accessor'), null, 'row_0', [
                        new ExpressionNode(new FunctionNode('\fputcsv', [
                            new VariableNode('resource'),
                            new ArrayNode([new VariableNode('row_0')]),
                            new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                            new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                            new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                            new ScalarNode(''),
                        ])),
                        new ExpressionNode(new FunctionNode('\fwrite', [
                            new VariableNode('resource'),
                            new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                        ])),
                    ]),
                ],
                [
                    [
                        'condition' => new BinaryNode(
                            '&&',
                            new FunctionNode('\is_object', [new FunctionNode('\reset', [new VariableNode('accessor')])]),
                            new FunctionNode('\is_subclass_of', [new FunctionNode('\get_class', [new FunctionNode('\reset', [new VariableNode('accessor')])]), new ScalarNode(\BackedEnum::class)])
                        ),
                        'body' => [
                            new ExpressionNode(new FunctionNode('\fputcsv', [
                                new VariableNode('resource'),
                                new ArrayNode([new ScalarNode(0)]),
                                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                                new ScalarNode(''),
                            ])),
                            new ExpressionNode(new FunctionNode('\fwrite', [
                                new VariableNode('resource'),
                                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                            ])),
                            new ForEachNode(new VariableNode('accessor'), null, 'row_0', [
                                new ExpressionNode(new FunctionNode('\fputcsv', [
                                    new VariableNode('resource'),
                                    new ArrayNode([new PropertyNode(new VariableNode('row_0'), 'value')]),
                                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                                    new ScalarNode(''),
                                ])),
                                new ExpressionNode(new FunctionNode('\fwrite', [
                                    new VariableNode('resource'),
                                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                                ])),
                            ]),
                        ],
                    ],
                    [
                        'condition' => new FunctionNode('\is_object', [new FunctionNode('\reset', [new VariableNode('accessor')])]),
                        'body' => [
                            new ExpressionNode(new FunctionNode('\fputcsv', [
                                new VariableNode('resource'),
                                new FunctionNode('\array_keys', [new CastNode('array', new FunctionNode('\reset', [new VariableNode('accessor')]))]),
                                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                                new ScalarNode(''),
                            ])),
                            new ExpressionNode(new FunctionNode('\fwrite', [
                                new VariableNode('resource'),
                                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                            ])),
                            new ForEachNode(new VariableNode('accessor'), null, 'row_0', [
                                new ExpressionNode(new FunctionNode('\fputcsv', [
                                    new VariableNode('resource'),
                                    new CastNode('array', new VariableNode('row_0')),
                                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                                    new ScalarNode(''),
                                ])),
                                new ExpressionNode(new FunctionNode('\fwrite', [
                                    new VariableNode('resource'),
                                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                                ])),
                            ]),
                        ],
                    ],
                ],
            ),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('mixed'), new VariableNode('accessor'), []));
    }

    public function testGenerateUnionList()
    {
        $this->assertEquals([
            new ExpressionNode(new FunctionNode('\fputcsv', [
                new VariableNode('resource'),
                new ArrayNode([new ScalarNode(0)]),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                new ScalarNode(''),
            ])),
            new ExpressionNode(new FunctionNode('\fwrite', [
                new VariableNode('resource'),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
            ])),
            new ForEachNode(new VariableNode('accessor'), null, 'row_0', [
                new ExpressionNode(new FunctionNode('\fputcsv', [
                    new VariableNode('resource'),
                    new ArrayNode([new VariableNode('row_0')]),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                    new ScalarNode(''),
                ])),
                new ExpressionNode(new FunctionNode('\fwrite', [
                    new VariableNode('resource'),
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                ])),
            ]),
        ], $this->templateGenerator->generate(TypeFactory::createFromString('array<int, int|array<int, string>>'), new VariableNode('accessor'), []));
    }

    public function testGenerateThrowWhenCannotGuessHeaders()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->templateGenerator->generate(TypeFactory::createFromString('array<int, mixed>'), new VariableNode('accessor'), []);
    }

    /**
     * @dataProvider generateThrowWhenNotAListDataProvider
     */
    public function testGenerateThrowWhenNotAList(string $type)
    {
        $this->expectException(UnexpectedValueException::class);
        $this->templateGenerator->generate(TypeFactory::createFromString($type), new VariableNode('accessor'), []);
    }

    /**
     * @return iterable<array{0: string}>
     */
    public static function generateThrowWhenNotAListDataProvider(): iterable
    {
        yield ['null'];
        yield ['int'];
        yield ['array<string, int>'];
        yield [ClassicDummy::class];
        yield [DummyBackedEnum::class];
    }

    /**
     * @dataProvider generateThrowWhenTooDeepDataProvider
     */
    public function testGenerateThrowWhenTooDeep(string $type)
    {
        $this->expectException(UnexpectedValueException::class);
        $this->templateGenerator->generate(TypeFactory::createFromString($type), new VariableNode('accessor'), []);
    }

    /**
     * @return iterable<array{0: string}>
     */
    public static function generateThrowWhenTooDeepDataProvider(): iterable
    {
        yield ['array<int, array<int, array<int, int>>>'];
        yield ['array<int, array<string, array<int, int>>>'];
        yield [sprintf('array<int, array<int, %s>>', ClassicDummy::class)];
    }
}
