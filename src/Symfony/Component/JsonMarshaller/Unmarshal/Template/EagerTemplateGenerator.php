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
                        new VariableNode('jsonDecodeFlags'),
                    ]), static: true),
                ]),
            ))),
        ];
    }

    protected function collectionNodes(CollectionNode $node, array &$context): array
    {
        $returnNullNodes = $node->type()->isNullable() ? [
            new IfNode(new BinaryNode('===', new PhpScalarNode(null), new VariableNode('data')), [
                new ExpressionNode(new ReturnNode(new PhpScalarNode(null))),
            ]),
        ] : [];

        $itemValueNode = $node->item instanceof ScalarNode
            ? $this->prepareScalarNode($node->item, new VariableNode('v'))
            : new FunctionCallNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->item->identifier())),
                new ArgumentsNode([new VariableNode('v')]),
            );

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

        $providerNodes = $node->item instanceof ScalarNode ? [] : $this->providerNodes($node->item, $context);

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

    protected function objectNodes(ObjectNode $node, array &$context): array
    {
        $returnNullNodes = $node->type()->isNullable() ? [
            new IfNode(new BinaryNode('===', new PhpScalarNode(null), new VariableNode('data')), [
                new ExpressionNode(new ReturnNode(new PhpScalarNode(null))),
            ]),
        ] : [];

        $propertyValueProvidersNodes = [];
        $fillPropertiesArrayNodes = [new ExpressionNode(new AssignNode(new VariableNode('properties'), new ArrayNode([])))];

        foreach ($node->properties as $marshalledName => $property) {
            $propertyValueProvidersNodes = [
                ...$propertyValueProvidersNodes,
                ...($property['value'] instanceof ScalarNode ? [] : $this->providerNodes($property['value'], $context)),
            ];

            $propertyValueNode = $property['value'] instanceof ScalarNode
                ? $this->prepareScalarNode($property['value'], new ArrayAccessNode(new VariableNode('data'), new PhpScalarNode($marshalledName)))
                : new FunctionCallNode(
                    new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($property['value']->identifier())),
                    new ArgumentsNode([new ArrayAccessNode(new VariableNode('data'), new PhpScalarNode($marshalledName))]),
                );

            $fillPropertiesArrayNodes[] = new IfNode(new FunctionCallNode(
                'isset',
                new ArgumentsNode([new ArrayAccessNode(new VariableNode('data'), new PhpScalarNode($marshalledName))]),
            ), [
                new ExpressionNode(new AssignNode(
                    new ArrayAccessNode(new VariableNode('properties'), new PhpScalarNode($property['name'])),
                    new ClosureNode(new ParametersNode([]), 'mixed', true, [
                        new ExpressionNode(new ReturnNode(($property['formatter'])($propertyValueNode))),
                    ], new ArgumentsNode([
                        new VariableNode('data'),
                        new VariableNode('config'),
                        new VariableNode('instantiator'),
                        new VariableNode('services'),
                        new VariableNode('providers', byReference: true),
                    ])),
                )),
            ]);
        }

        $instantiateNodes = [
            new ExpressionNode(new ReturnNode(new MethodCallNode(
                new VariableNode('instantiator'),
                'instantiate',
                new ArgumentsNode([new PhpScalarNode($node->type()->className()), new VariableNode('properties')]),
            ))),
        ];

        return [
            new ExpressionNode(new AssignNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->identifier())),
                new ClosureNode(
                    new ParametersNode(['data' => '?array']),
                    ($node->type()->isNullable() ? '?' : '').$node->type()->className(),
                    true,
                    [...$returnNullNodes, ...$fillPropertiesArrayNodes, ...$instantiateNodes],
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

    protected function scalarNodes(ScalarNode $node, array &$context): array
    {
        return [
            new ExpressionNode(new AssignNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->identifier())),
                new ClosureNode(
                    new ParametersNode(['data' => 'mixed']),
                    'mixed',
                    true,
                    [new ExpressionNode(new ReturnNode($this->prepareScalarNode($node, new VariableNode('data'))))],
                ),
            )),
        ];
    }

    private function prepareScalarNode(ScalarNode $node, PhpNodeInterface $accessor): PhpNodeInterface
    {
        if ($node->type()->isBackedEnum()) {
            return new MethodCallNode(
                new PhpScalarNode($node->type()->className()),
                $node->type()->isNullable() ? 'tryFrom' : 'from',
                new ArgumentsNode([$accessor]),
                static: true,
            );
        }

        if ('object' === $node->type()->name()) {
            return new CastNode('object', $accessor);
        }

        return $accessor;
    }
}
