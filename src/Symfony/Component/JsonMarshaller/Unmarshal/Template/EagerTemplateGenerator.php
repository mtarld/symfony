<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Unmarshal\Template;

use Symfony\Component\JsonMarshaller\Exception\UnexpectedValueException;
use Symfony\Component\JsonMarshaller\Php\ArgumentsNode;
use Symfony\Component\JsonMarshaller\Php\ArrayAccessNode;
use Symfony\Component\JsonMarshaller\Php\ArrayNode;
use Symfony\Component\JsonMarshaller\Php\AssignNode;
use Symfony\Component\JsonMarshaller\Php\BinaryNode;
use Symfony\Component\JsonMarshaller\Php\CastNode;
use Symfony\Component\JsonMarshaller\Php\ClosureNode;
use Symfony\Component\JsonMarshaller\Php\ExpressionNode;
use Symfony\Component\JsonMarshaller\Php\ForEachNode;
use Symfony\Component\JsonMarshaller\Php\FunctionCallNode;
use Symfony\Component\JsonMarshaller\Php\IfNode;
use Symfony\Component\JsonMarshaller\Php\MethodCallNode;
use Symfony\Component\JsonMarshaller\Php\NewNode;
use Symfony\Component\JsonMarshaller\Php\ParametersNode;
use Symfony\Component\JsonMarshaller\Php\PhpNodeInterface;
use Symfony\Component\JsonMarshaller\Php\ReturnNode;
use Symfony\Component\JsonMarshaller\Php\ScalarNode as PhpScalarNode;
use Symfony\Component\JsonMarshaller\Php\ThrowNode;
use Symfony\Component\JsonMarshaller\Php\TryCatchNode;
use Symfony\Component\JsonMarshaller\Php\VariableNode;
use Symfony\Component\JsonMarshaller\Php\YieldNode;
use Symfony\Component\JsonMarshaller\Unmarshal\DataModel\CollectionNode;
use Symfony\Component\JsonMarshaller\Unmarshal\DataModel\DataModelNodeInterface;
use Symfony\Component\JsonMarshaller\Unmarshal\DataModel\ObjectNode;
use Symfony\Component\JsonMarshaller\Unmarshal\DataModel\ScalarNode;

/**
 * Generates a template PHP syntax tree that unmarshals data eagerly.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class EagerTemplateGenerator extends TemplateGenerator
{
    protected function returnDataNodes(DataModelNodeInterface $node, array &$context): array
    {
        return [
            new ExpressionNode(new ReturnNode(new FunctionCallNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->identifier())),
                new ArgumentsNode([
                    new MethodCallNode(new PhpScalarNode('\\'.Decoder::class), 'decode', new ArgumentsNode([
                        new VariableNode('resource'),
                        new PhpScalarNode(0),
                        new PhpScalarNode(-1),
                        new VariableNode('config'),
                    ]), static: true),
                ]),
            ))),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    protected function collectionNodes(CollectionNode $node, array &$context): array
    {
        $returnNullNodes = $node->type->isNullable() ? [
            new IfNode(new BinaryNode('===', new PhpScalarNode(null), new VariableNode('data')), [
                new ExpressionNode(new ReturnNode(new PhpScalarNode(null))),
            ]),
        ] : [];

        $iterableClosureNodes = [
            new ExpressionNode(new AssignNode(
                new VariableNode('iterable'),
                new ClosureNode(new ParametersNode(['data' => 'iterable']), 'iterable', true, [
                    new ForEachNode(new VariableNode('data'), new VariableNode('k'), new VariableNode('v'), [
                        new ExpressionNode(new YieldNode(
                            new FunctionCallNode(
                                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->item->identifier())),
                                new ArgumentsNode([new VariableNode('v')]),
                            ),
                            new VariableNode('k'),
                        )),
                    ]),
                ], new ArgumentsNode([
                    new VariableNode('config'),
                    new VariableNode('instantiator'),
                    new VariableNode('providers', byReference: true),
                ])),
            )),
        ];

        $iterableValueNode = new FunctionCallNode(new VariableNode('iterable'), new ArgumentsNode([new VariableNode('data')]));

        $returnNodes = [
            new ExpressionNode(new ReturnNode(
                'array' === $node->type->name() ? new FunctionCallNode('\iterator_to_array', new ArgumentsNode([$iterableValueNode])) : $iterableValueNode,
            )),
        ];

        return [
            new ExpressionNode(new AssignNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->identifier())),
                new ClosureNode(
                    new ParametersNode(['data' => '?iterable']),
                    ($node->type->isNullable() ? '?' : '').$node->type->name(),
                    true,
                    [...$returnNullNodes, ...$iterableClosureNodes, ...$returnNodes],
                    new ArgumentsNode([
                        new VariableNode('config'),
                        new VariableNode('instantiator'),
                        new VariableNode('providers', byReference: true),
                    ]),
                ),
            )),
            ...$this->providerNodes($node->item, $context),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    protected function objectNodes(ObjectNode $node, array &$context): array
    {
        $returnNullNodes = $node->type->isNullable() ? [
            new IfNode(new BinaryNode('===', new PhpScalarNode(null), new VariableNode('data')), [
                new ExpressionNode(new ReturnNode(new PhpScalarNode(null))),
            ]),
        ] : [];

        $propertyValueProvidersNodes = [];
        $fillPropertiesArrayNodes = [
            new ExpressionNode(new AssignNode(new VariableNode('properties'), new ArrayNode([]))),
        ];

        foreach ($node->properties as $marshalledName => $property) {
            array_push($propertyValueProvidersNodes, ...$this->providerNodes($property['value'], $context));

            $fillPropertiesArrayNodes[] = new IfNode(new FunctionCallNode(
                'isset',
                new ArgumentsNode([new ArrayAccessNode(new VariableNode('data'), new PhpScalarNode($marshalledName))]),
            ), [
                new ExpressionNode(new AssignNode(
                    new ArrayAccessNode(new VariableNode('properties'), new PhpScalarNode($property['name'])),
                    new ClosureNode(new ParametersNode([]), 'mixed', true, [
                        new ExpressionNode(new ReturnNode(($property['formatter'])(new FunctionCallNode(
                            new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($property['value']->identifier())),
                            new ArgumentsNode([new ArrayAccessNode(new VariableNode('data'), new PhpScalarNode($marshalledName))]),
                        )))),
                    ], new ArgumentsNode([
                        new VariableNode('data'),
                        new VariableNode('config'),
                        new VariableNode('instantiator'),
                        new VariableNode('providers', byReference: true),
                    ])),
                )),
            ]);
        }

        $instantiateNodes = [
            new ExpressionNode(new ReturnNode(new MethodCallNode(
                new VariableNode('instantiator'),
                'instantiate',
                new ArgumentsNode([new PhpScalarNode($node->type->className()), new VariableNode('properties')]),
            ))),
        ];

        return [
            new ExpressionNode(new AssignNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->identifier())),
                new ClosureNode(
                    new ParametersNode(['data' => '?array']),
                    ($node->type->isNullable() ? '?' : '').$node->type->className(),
                    true,
                    [...$returnNullNodes, ...$fillPropertiesArrayNodes, ...$instantiateNodes],
                    new ArgumentsNode([
                        new VariableNode('config'),
                        new VariableNode('instantiator'),
                        new VariableNode('providers', byReference: true),
                    ]),
                ),
            )),
            ...$propertyValueProvidersNodes,
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    protected function scalarNodes(ScalarNode $node, array &$context): array
    {
        $returnNullNodes = $node->type->isNullable() ? [
            new IfNode(new BinaryNode('===', new PhpScalarNode(null), new VariableNode('data')), [
                new ExpressionNode(new ReturnNode(new PhpScalarNode(null))),
            ]),
        ] : [];

        $formatDataNodes = match (true) {
            \in_array($node->type->name(), ['int', 'string', 'float', 'bool', 'object', 'array'], true) => [
                new TryCatchNode([new ExpressionNode(new ReturnNode(new CastNode($node->type->name(), new VariableNode('data'))))], [
                    new ExpressionNode(new ThrowNode(new NewNode('\\'.UnexpectedValueException::class, new ArgumentsNode([
                        new FunctionCallNode('sprintf', new ArgumentsNode([
                            new PhpScalarNode(sprintf('Cannot cast "%%s" to "%s"', $node->type->name())),
                            new FunctionCallNode('get_debug_type', new ArgumentsNode([new VariableNode('data')])),
                        ])),
                    ])))),
                ], new ParametersNode(['e' => '\\Throwable'])),
            ],
            $node->type->isBackedEnum() => [
                new TryCatchNode([
                    new ExpressionNode(new ReturnNode(new MethodCallNode(
                        new PhpScalarNode($node->type->className()),
                        'from',
                        new ArgumentsNode([new VariableNode('data')]),
                        static: true,
                    ))),
                ], [
                    new ExpressionNode(new ThrowNode(new NewNode('\\'.UnexpectedValueException::class, new ArgumentsNode([
                        new FunctionCallNode('sprintf', new ArgumentsNode([
                            new PhpScalarNode(sprintf('Unexpected "%%s" value for "%s" backed enumeration.', $node->type)),
                            new VariableNode('data'),
                        ])),
                    ])))),
                ], new ParametersNode(['e' => '\\ValueError'])),
            ],
            default => [
                new ExpressionNode(new ReturnNode(new VariableNode('data'))),
            ],
        };

        return [
            new ExpressionNode(new AssignNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->identifier())),
                new ClosureNode(
                    new ParametersNode(['data' => 'mixed']),
                    'mixed',
                    true,
                    [...$returnNullNodes, ...$formatDataNodes],
                    new ArgumentsNode([
                        new VariableNode('config'),
                        new VariableNode('instantiator'),
                        new VariableNode('providers', byReference: true),
                    ]),
                ),
            )),
        ];
    }
}
