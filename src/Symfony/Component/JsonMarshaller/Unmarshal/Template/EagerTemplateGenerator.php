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

use Symfony\Component\JsonMarshaller\Exception\LogicException;
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
use Symfony\Component\JsonMarshaller\Php\ParametersNode;
use Symfony\Component\JsonMarshaller\Php\PhpNodeInterface;
use Symfony\Component\JsonMarshaller\Php\ReturnNode;
use Symfony\Component\JsonMarshaller\Php\ScalarNode as PhpScalarNode;
use Symfony\Component\JsonMarshaller\Php\TernaryConditionNode;
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
final readonly class EagerTemplateGenerator
{
    /**
     * @param UnmarshalConfig      $config
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    public function generate(DataModelNodeInterface $node, array $config, array $context): array
    {
        return [
            new ExpressionNode(new AssignNode(new VariableNode('jsonDecodeFlags'), new BinaryNode(
                '??',
                new ArrayAccessNode(new VariableNode('config'), new PhpScalarNode('json_decode_flags')),
                new PhpScalarNode(0),
            ))),
            ...$this->providerNodes($node, $context),
            new ExpressionNode(new ReturnNode(new FunctionCallNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->identifier())),
                new ArgumentsNode([
                    new MethodCallNode(new PhpScalarNode('\\'.Decoder::class), 'decode', new ArgumentsNode([
                        new VariableNode('resource'),
                        new PhpScalarNode(0),
                        new PhpScalarNode(-1),
                        new VariableNode('jsonDecodeFlags'),
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
    private function providerNodes(DataModelNodeInterface $node, array &$context): array
    {
        if ($context['providers'][$node->identifier()] ?? false) {
            return [];
        }

        $context['providers'][$node->identifier()] = true;

        return match (true) {
            !$this->twistJson($node) => $this->rawJsonNodes($node),
            $node instanceof CollectionNode => $this->collectionNodes($node, $context),
            $node instanceof ObjectNode => $this->objectNodes($node, $context),
            $node instanceof ScalarNode => $this->scalarNodes($node),
            default => throw new LogicException(sprintf('Unexpected "%s" node', $node::class)),
        };
    }

    /**
     * @return list<PhpNodeInterface>
     */
    private function rawJsonNodes(DataModelNodeInterface $node): array
    {
        return [
            new ExpressionNode(new AssignNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->identifier())),
                new ClosureNode(
                    new ParametersNode(['data' => 'mixed']),
                    'mixed',
                    true,
                    [new ExpressionNode(new ReturnNode(new VariableNode('data')))],
                ),
            )),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    private function collectionNodes(CollectionNode $node, array &$context): array
    {
        $returnNullNodes = $node->type()->isNullable() ? [
            new IfNode(new BinaryNode('===', new PhpScalarNode(null), new VariableNode('data')), [
                new ExpressionNode(new ReturnNode(new PhpScalarNode(null))),
            ]),
        ] : [];

        $itemValueNode = $this->twistJson($node->item)
            ? new FunctionCallNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->item->identifier())),
                new ArgumentsNode([new VariableNode('v')]),
            )
            : new VariableNode('v');

        $iterableClosureNodes = [
            new ExpressionNode(new AssignNode(
                new VariableNode('iterable'),
                new ClosureNode(new ParametersNode(['data' => 'iterable']), 'iterable', true, [
                    new ForEachNode(new VariableNode('data'), new VariableNode('k'), new VariableNode('v'), [
                        new ExpressionNode(new YieldNode(
                            $itemValueNode,
                            new VariableNode('k'),
                        )),
                    ]),
                ], new ArgumentsNode([
                    new VariableNode('config'),
                    new VariableNode('instantiator'),
                    new VariableNode('services'),
                    new VariableNode('providers', byReference: true),
                ])),
            )),
        ];

        $iterableValueNode = new FunctionCallNode(new VariableNode('iterable'), new ArgumentsNode([new VariableNode('data')]));

        $returnNodes = [
            new ExpressionNode(new ReturnNode(
                'array' === $node->type()->name() ? new FunctionCallNode('\iterator_to_array', new ArgumentsNode([$iterableValueNode])) : $iterableValueNode,
            )),
        ];

        $providerNodes = $this->twistJson($node->item) ? $this->providerNodes($node->item, $context) : [];

        return [
            new ExpressionNode(new AssignNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->identifier())),
                new ClosureNode(
                    new ParametersNode(['data' => '?iterable']),
                    ($node->type()->isNullable() ? '?' : '').$node->type()->name(),
                    true,
                    [...$returnNullNodes, ...$iterableClosureNodes, ...$returnNodes],
                    new ArgumentsNode([
                        new VariableNode('config'),
                        new VariableNode('instantiator'),
                        new VariableNode('services'),
                        new VariableNode('providers', byReference: true),
                    ]),
                ),
            )),
            ...$providerNodes,
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    private function objectNodes(ObjectNode $node, array &$context): array
    {
        if ($node->ghost) {
            return [];
        }

        $returnNullNodes = $node->type()->isNullable() ? [
            new IfNode(new BinaryNode('===', new PhpScalarNode(null), new VariableNode('data')), [
                new ExpressionNode(new ReturnNode(new PhpScalarNode(null))),
            ]),
        ] : [];

        $propertyValueProvidersNodes = [];
        $propertiesValues = [];

        foreach ($node->properties as $marshalledName => $property) {
            $propertyValueProvidersNodes = [
                ...$propertyValueProvidersNodes,
                ...($this->twistJson($property['value']) ? $this->providerNodes($property['value'], $context) : []),
            ];

            $propertyValueNode = $this->twistJson($property['value'])
                ? new TernaryConditionNode(
                    new FunctionCallNode('\array_key_exists', new ArgumentsNode([new PhpScalarNode($marshalledName), new VariableNode('data')])),
                    new FunctionCallNode(
                        new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($property['value']->identifier())),
                        new ArgumentsNode([new ArrayAccessNode(new VariableNode('data'), new PhpScalarNode($marshalledName))]),
                    ),
                    new PhpScalarNode('_symfony_missing_value'),
                )
                : new BinaryNode('??', new ArrayAccessNode(new VariableNode('data'), new PhpScalarNode($marshalledName)), new PhpScalarNode('_symfony_missing_value'));

            $propertiesValues[$property['name']] = $property['formatter']($propertyValueNode);
        }

        return [
            new ExpressionNode(new AssignNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->identifier())),
                new ClosureNode(
                    new ParametersNode(['data' => '?array']),
                    ($node->type()->isNullable() ? '?' : '').$node->type()->className(),
                    true,
                    [
                        ...$returnNullNodes,

                        // new ExpressionNode(new ReturnNode(
                        //     new FunctionCallNode('\unserialize', new ArgumentsNode([
                        //         new FunctionCallNode('\sprintf', new ArgumentsNode([
                        //             new PhpScalarNode(sprintf(
                        //                 'O:%d:"%s"%%s',
                        //                 strlen($node->type()->className()),
                        //                 $node->type()->className(),
                        //             )),
                        //             new FunctionCallNode('\strstr', new ArgumentsNode([
                        //                 new FunctionCallNode('\strstr', new ArgumentsNode([
                        //                     new FunctionCallNode('\serialize', new ArgumentsNode([
                        //                         new CastNode('object', new FunctionCallNode('\array_filter', new ArgumentsNode([
                        //                             new ArrayNode($propertiesValues),
                        //                             new ClosureNode(new ParametersNode(['v' => 'mixed']), 'bool', true, [
                        //                                 new ExpressionNode(new ReturnNode(new BinaryNode('!==', new PhpScalarNode('_symfony_missing_value'), new VariableNode('v')))),
                        //                             ]),
                        //                         ]))),
                        //                     ])),
                        //                     new PhpScalarNode('"'),
                        //                 ])),
                        //                 new PhpScalarNode(':'),
                        //             ])),
                        //         ])),
                        //     ])),
                        // )),

                        new ExpressionNode(new ReturnNode(new MethodCallNode(
                            new VariableNode('instantiator'),
                            'instantiate',
                            new ArgumentsNode([
                                new PhpScalarNode($node->type()->className()),
                                new FunctionCallNode('\array_filter', new ArgumentsNode([
                                    new ArrayNode($propertiesValues),
                                    new ClosureNode(new ParametersNode(['v' => 'mixed']), 'bool', true, [
                                        new ExpressionNode(new ReturnNode(new BinaryNode('!==', new PhpScalarNode('_symfony_missing_value'), new VariableNode('v')))),
                                    ]),
                                ])),
                            ]),
                        ))),
                    ],
                    new ArgumentsNode([
                        new VariableNode('config'),
                        new VariableNode('instantiator'),
                        new VariableNode('services'),
                        new VariableNode('providers', byReference: true),
                    ]),
                ),
            )),
            ...$propertyValueProvidersNodes,
        ];
    }

    /**
     * @return list<PhpNodeInterface>
     */
    private function scalarNodes(ScalarNode $node): array
    {
        $returnNullNodes = $node->type()->isNullable() ? [
            new IfNode(new BinaryNode('===', new PhpScalarNode(null), new VariableNode('data')), [
                new ExpressionNode(new ReturnNode(new PhpScalarNode(null))),
            ]),
        ] : [];

        $accessor = match (true) {
            'object' === $node->type()->name() => new CastNode('object', new VariableNode('data')),
            $node->type->isBackedEnum() => new MethodCallNode(
                new PhpScalarNode($node->type->className()),
                'from',
                new ArgumentsNode([new VariableNode('data')]),
                static: true,
            ),
            default => new VariableNode('data'),
        };

        return [
            new ExpressionNode(new AssignNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->identifier())),
                new ClosureNode(
                    new ParametersNode(['data' => 'mixed']),
                    'mixed',
                    true,
                    [
                        ...$returnNullNodes,
                        new ExpressionNode(new ReturnNode($accessor)),
                    ],
                ),
            )),
        ];
    }

    private function twistJson(DataModelNodeInterface $node): bool
    {
        if ($node->isTransformed()) {
            return true;
        }

        if ($node->type()->isObject()) {
            return true;
        }

        if ($node instanceof CollectionNode) {
            return $this->twistJson($node->item);
        }

        if ($node instanceof ObjectNode) {
            foreach ($node->properties as $property) {
                if ($this->twistJson($property['value'])) {
                    return true;
                }
            }
        }

        return false;
    }
}
