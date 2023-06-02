<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Internal\Serialize\TemplateGenerator;

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
use Symfony\Component\SerDes\Internal\Serialize\NodeInterface;
use Symfony\Component\SerDes\Internal\Type;
use Symfony\Component\SerDes\Internal\TypeFactory;
use Symfony\Component\SerDes\Internal\UnionType;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class CsvTemplateGenerator extends TemplateGenerator
{
    protected function nullNodes(array $context): array
    {
        $depth = $context['csv_depth'] ?? 0;

        if (0 === $depth) {
            throw $this->notAListException(TypeFactory::createFromString('null'));
        }

        if (1 === $depth) {
            return $this->fputcsvNodes(new ArrayNode([new ScalarNode(null)]));
        }

        throw $this->tooDeepException();
    }

    protected function scalarNodes(Type $type, NodeInterface $accessor, array $context): array
    {
        $depth = $context['csv_depth'] ?? 0;

        if (0 === $depth) {
            throw $this->notAListException($type);
        }

        if (1 === $depth) {
            return $this->fputcsvNodes(new ArrayNode([$accessor]));
        }

        throw $this->tooDeepException();
    }

    protected function listNodes(Type $type, NodeInterface $accessor, array $context): array
    {
        $depth = $context['csv_depth'] ?? 0;

        if (0 === $depth) {
            $collectionValueType = $type->collectionValueType();
            if ($collectionValueType instanceof UnionType) {
                $collectionValueTypes = $this->typeSorter->sortByPrecision($collectionValueType->types);
                /** @var Type $collectionValueType */
                $collectionValueType = reset($collectionValueTypes);
            }

            $headersName = $this->scopeVariableName('headers', $context);
            $flippedHeadersName = $this->scopeVariableName('flippedHeaders', $context);
            $rowName = $this->scopeVariableName('row', $context);

            $headerNodes = match (true) {
                $collectionValueType->isScalar() || $collectionValueType->isEnum() || $collectionValueType->isNull() => [
                    ...$this->fputcsvNodes(new ArrayNode([new ScalarNode(0)])),
                    new ExpressionNode(new FunctionNode('\fwrite', [
                        new VariableNode('resource'),
                        new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                    ])),
                ],
                $collectionValueType->isList() => [
                    ...$this->fputcsvNodes(new FunctionNode('\array_keys', [new FunctionNode('\reset', [$accessor])])),
                    new ExpressionNode(new FunctionNode('\fwrite', [
                        new VariableNode('resource'),
                        new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                    ])),
                ],
                $collectionValueType->isDict() => [
                    new ExpressionNode(new AssignNode(
                        new VariableNode($headersName),
                        new FunctionNode('\array_reduce', [
                            $accessor,
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
                    new ExpressionNode(new AssignNode(new VariableNode($flippedHeadersName), new FunctionNode('\array_fill_keys', [new VariableNode($headersName), new ScalarNode('')]))),
                    ...$this->fputcsvNodes(new VariableNode($headersName)),
                    new ExpressionNode(new FunctionNode('\fwrite', [
                        new VariableNode('resource'),
                        new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                    ])),
                ],
                $collectionValueType->isObject() => [
                    new ExpressionNode(new AssignNode(new VariableNode($headersName), new FunctionNode('\array_keys', [new CastNode('array', new FunctionNode('\reset', [$accessor]))]))),
                    new ExpressionNode(new AssignNode(new VariableNode($flippedHeadersName), new FunctionNode('\array_fill_keys', [new VariableNode($headersName), new ScalarNode('')]))),
                    ...$this->fputcsvNodes(new VariableNode($headersName)),
                    new ExpressionNode(new FunctionNode('\fwrite', [
                        new VariableNode('resource'),
                        new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                    ])),
                ],
                default => throw new UnexpectedValueException(sprintf('Cannot guess headers for type "%s".', (string) $collectionValueType)),
            };

            return [
                ...$headerNodes,
                new ForEachNode($accessor, null, $rowName, [
                    ...$this->generate($collectionValueType, new VariableNode($rowName), $context + [
                        'flipped_headers_accessor' => new VariableNode($flippedHeadersName),
                        'csv_depth' => $depth + 1,
                    ]),
                    new ExpressionNode(new FunctionNode('\fwrite', [
                        new VariableNode('resource'),
                        new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                    ])),
                ]),
            ];
        }

        if (1 === $depth) {
            $collectionValueType = $type->collectionValueType();

            if ($collectionValueType instanceof Type && !$collectionValueType->isScalar() && !$collectionValueType->isEnum() && !$collectionValueType->isNull()) {
                throw $this->tooDeepException();
            }

            if ($collectionValueType instanceof Type && $collectionValueType->isEnum()) {
                $accessor = new FunctionNode('\array_map', [new ClosureNode(new ArgumentsNode(['e' => '\\'.\BackedEnum::class]), 'int|string', true, [
                    new ExpressionNode(new ReturnNode(new PropertyNode(new VariableNode('e'), 'value'))),
                ]), $accessor]);
            }

            return $this->fputcsvNodes($accessor);
        }

        throw $this->tooDeepException();
    }

    protected function dictNodes(Type $type, NodeInterface $accessor, array $context): array
    {
        $depth = $context['csv_depth'] ?? 0;

        if (0 === $depth) {
            throw $this->notAListException($type);
        }

        if (1 === $depth) {
            $collectionValueType = $type->collectionValueType();

            if ($collectionValueType instanceof Type && !$collectionValueType->isScalar() && !$collectionValueType->isEnum() && !$collectionValueType->isNull()) {
                throw $this->tooDeepException();
            }

            if ($collectionValueType instanceof Type && $collectionValueType->isEnum()) {
                $accessor = new FunctionNode('\array_map', [new ClosureNode(new ArgumentsNode(['e' => '\\'.\BackedEnum::class]), 'int|string', true, [
                    new ExpressionNode(new ReturnNode(new PropertyNode(new VariableNode('e'), 'value'))),
                ]), $accessor]);
            }

            return $this->fputcsvNodes(new FunctionNode('\array_replace', [$context['flipped_headers_accessor'], $accessor]));
        }

        throw $this->tooDeepException();
    }

    protected function objectNodes(Type $type, array $propertiesInfo, array $context): array
    {
        $depth = $context['csv_depth'] ?? 0;

        if (0 === $depth) {
            $this->notAListException($type);
        }

        if (1 === $depth) {
            $indexedPropertiesInfo = [];
            foreach ($propertiesInfo as $propertyInfo) {
                $indexedPropertiesInfo[$propertyInfo['name']] = $propertyInfo;
            }

            $nodes = [];
            $prefix = '';

            $properties = array_map(fn (\ReflectionProperty $p): string => $p->name, (new \ReflectionClass($type->className()))->getProperties());

            foreach ($properties as $property) {
                $nodes[] = new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($prefix)]));
                $prefix = $context['csv_separator'] ?? ',';

                if (null === $propertyInfo = $indexedPropertiesInfo[$property] ?? null) {
                    continue;
                }

                array_push($nodes, ...$this->generate(TypeFactory::createFromString($propertyInfo['type']), $propertyInfo['accessor'], $propertyInfo['context']));
            }

            return $nodes;
        }

        throw $this->tooDeepException();
    }

    protected function mixedNodes(NodeInterface $accessor, array $context): array
    {
        $depth = $context['csv_depth'] ?? 0;

        if (0 === $depth) {
            $rowName = $this->scopeVariableName('row', $context);
            $headersName = $this->scopeVariableName('headers', $context);
            $flippedHeadersName = $this->scopeVariableName('flippedHeaders', $context);

            return [
                new IfNode(new UnaryNode('!', new FunctionNode('\is_iterable', [$accessor])), [
                    new ExpressionNode(new ThrowNode(new NewNode('\\'.UnexpectedValueException::class, [new FunctionNode('\sprintf', [
                        new ScalarNode('Expecting first level data type to be a list, but got "%s".'),
                        new FunctionNode('\get_debug_type', [$accessor]),
                    ])]))),
                ]),
                new IfNode(
                    new FunctionNode('\is_iterable', [new FunctionNode('\reset', [$accessor])]),
                    [
                        new ExpressionNode(new AssignNode(
                            new VariableNode($headersName),
                            new FunctionNode('\array_reduce', [
                                $accessor,
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
                        new ExpressionNode(new AssignNode(new VariableNode($flippedHeadersName), new FunctionNode('\array_fill_keys', [new VariableNode($headersName), new ScalarNode('')]))),
                        ...$this->fputcsvNodes(new VariableNode($headersName)),
                        new ExpressionNode(new FunctionNode('\fwrite', [
                            new VariableNode('resource'),
                            new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                        ])),
                        new ForEachNode($accessor, null, $rowName, [
                            ...$this->fputcsvNodes(new FunctionNode('\array_replace', [new VariableNode($flippedHeadersName), new VariableNode($rowName)])),
                            new ExpressionNode(new FunctionNode('\fwrite', [
                                new VariableNode('resource'),
                                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                            ])),
                        ]),
                    ],
                    [
                        ...$this->fputcsvNodes(new ArrayNode([new ScalarNode(0)])),
                        new ExpressionNode(new FunctionNode('\fwrite', [
                            new VariableNode('resource'),
                            new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                        ])),
                        new ForEachNode($accessor, null, $rowName, [
                            ...$this->fputcsvNodes(new ArrayNode([new VariableNode($rowName)])),
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
                                new FunctionNode('\is_object', [new FunctionNode('\reset', [$accessor])]),
                                new FunctionNode('\is_subclass_of', [new FunctionNode('\get_class', [new FunctionNode('\reset', [$accessor])]), new ScalarNode(\BackedEnum::class)])
                            ),
                            'body' => [
                                ...$this->fputcsvNodes(new ArrayNode([new ScalarNode(0)])),
                                new ExpressionNode(new FunctionNode('\fwrite', [
                                    new VariableNode('resource'),
                                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                                ])),
                                new ForEachNode($accessor, null, $rowName, [
                                    ...$this->fputcsvNodes(new ArrayNode([new PropertyNode(new VariableNode($rowName), 'value')])),
                                    new ExpressionNode(new FunctionNode('\fwrite', [
                                        new VariableNode('resource'),
                                        new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                                    ])),
                                ]),
                            ],
                        ],
                        [
                            'condition' => new FunctionNode('\is_object', [new FunctionNode('\reset', [$accessor])]),
                            'body' => [
                                ...$this->fputcsvNodes(new FunctionNode('\array_keys', [new CastNode('array', new FunctionNode('\reset', [$accessor]))])),
                                new ExpressionNode(new FunctionNode('\fwrite', [
                                    new VariableNode('resource'),
                                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                                ])),
                                new ForEachNode($accessor, null, $rowName, [
                                    ...$this->fputcsvNodes(new CastNode('array', new VariableNode($rowName))),
                                    new ExpressionNode(new FunctionNode('\fwrite', [
                                        new VariableNode('resource'),
                                        new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_end_of_line')), new ScalarNode("\n")),
                                    ])),
                                ]),
                            ],
                        ],
                    ],
                ),
            ];
        }

        if (1 === $depth) {
            $rowName = $this->scopeVariableName('row', $context);

            return [
                new IfNode(
                    new FunctionNode('\is_iterable', [$accessor]),
                    $this->fputcsvNodes(new FunctionNode('\array_replace', [$context['flipped_headers_accessor'], $accessor])),
                    $this->fputcsvNodes(new ArrayNode([$accessor])),
                    [
                        [
                            'condition' => new BinaryNode(
                                '&&',
                                new FunctionNode('\is_object', [$accessor]),
                                new FunctionNode('\is_subclass_of', [new FunctionNode('\get_class', [$accessor]), new ScalarNode(\BackedEnum::class)])
                            ),
                            'body' => $this->fputcsvNodes(new ArrayNode([new PropertyNode($accessor, 'value')])),
                        ],
                        [
                            'condition' => new FunctionNode('\is_object', [$accessor]),
                            'body' => $this->fputcsvNodes(new CastNode('array', $accessor)),
                        ],
                    ],
                ),
            ];
        }

        throw $this->tooDeepException();
    }

    /**
     * @return list<NodeInterface>
     */
    private function fputcsvNodes(NodeInterface $data): array
    {
        return [
            new ExpressionNode(new FunctionNode('\fputcsv', [
                new VariableNode('resource'),
                $data,
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_separator')), new ScalarNode(',')),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_enclosure')), new ScalarNode('"')),
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('csv_escape_char')), new ScalarNode('\\')),
                new ScalarNode(''),
            ])),
        ];
    }

    private function notAListException(Type|UnionType $type): \Throwable
    {
        return new UnexpectedValueException(sprintf('Expecting first level data type to be a list, but got "%s".', (string) $type));
    }

    private function tooDeepException(): \Throwable
    {
        return new UnexpectedValueException('Expecting data to have at most 2 dimensions.');
    }
}
