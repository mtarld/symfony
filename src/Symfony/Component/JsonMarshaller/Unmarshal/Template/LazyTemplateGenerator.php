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
use Symfony\Component\JsonMarshaller\Php\ClosureNode;
use Symfony\Component\JsonMarshaller\Php\ContinueNode;
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
 * Generates a template PHP syntax tree that unmarshals data lazily.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class LazyTemplateGenerator extends TemplateGenerator
{
    protected function returnDataNodes(DataModelNodeInterface $node, array &$context): array
    {
        return [
            new ExpressionNode(new ReturnNode(new FunctionCallNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->identifier())),
                new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode(0), new PhpScalarNode(-1)]),
            ))),
        ];
    }

    protected function collectionNodes(CollectionNode $node, array &$context): array
    {
        $getBoundariesNodes = [
            new ExpressionNode(new AssignNode(new VariableNode('boundaries'), new MethodCallNode(
                new PhpScalarNode('\\'.Splitter::class),
                $node->type()->isList() ? 'splitList' : 'splitDict',
                new ArgumentsNode([new VariableNode('resource'), new VariableNode('offset'), new VariableNode('length')]),
                static: true,
            ))),
        ];

        if ($node->type()->isNullable()) {
            $getBoundariesNodes[] = new IfNode(new BinaryNode('===', new PhpScalarNode(null), new VariableNode('boundaries')), [
                new ExpressionNode(new ReturnNode(new PhpScalarNode(null))),
            ]);
        }

        $itemValueNode = $node->item instanceof ScalarNode
            ? $this->prepareScalarNode(
                $node->item,
                new ArrayAccessNode(new VariableNode('b'), new PhpScalarNode(0)),
                new ArrayAccessNode(new VariableNode('b'), new PhpScalarNode(1)),
            ) : new FunctionCallNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->item->identifier())),
                new ArgumentsNode([
                    new VariableNode('resource'),
                    new ArrayAccessNode(new VariableNode('b'), new PhpScalarNode(0)),
                    new ArrayAccessNode(new VariableNode('b'), new PhpScalarNode(1)),
                ]),
            );

        $iterableClosureNodes = [
            new ExpressionNode(new AssignNode(
                new VariableNode('iterable'),
                new ClosureNode(new ParametersNode(['resource' => 'mixed', 'boundaries' => 'iterable']), 'iterable', true, [
                    new ForEachNode(new VariableNode('boundaries'), new VariableNode('k'), new VariableNode('b'), [
                        new ExpressionNode(new YieldNode(
                            $itemValueNode,
                            new VariableNode('k'),
                        )),
                    ]),
                ], new ArgumentsNode([
                    new VariableNode('config'),
                    new VariableNode('instantiator'),
                    new VariableNode('providers', byReference: true),
                    new VariableNode('jsonDecodeFlags'),
                ])),
            )),
        ];

        $iterableValueNode = new FunctionCallNode(
            new VariableNode('iterable'),
            new ArgumentsNode([new VariableNode('resource'), new VariableNode('boundaries')]),
        );

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
                    new ParametersNode(['resource' => 'mixed', 'offset' => 'int', 'length' => 'int']),
                    ($node->type()->isNullable() ? '?' : '').$node->type()->name(),
                    true,
                    [...$getBoundariesNodes, ...$iterableClosureNodes, ...$returnNodes],
                    new ArgumentsNode([
                        new VariableNode('config'),
                        new VariableNode('instantiator'),
                        new VariableNode('providers', byReference: true),
                        new VariableNode('jsonDecodeFlags'),
                    ]),
                ),
            )),
            ...$providerNodes,
        ];
    }

    protected function objectNodes(ObjectNode $node, array &$context): array
    {
        $getBoundariesNodes = [
            new ExpressionNode(new AssignNode(new VariableNode('boundaries'), new MethodCallNode(
                new PhpScalarNode('\\'.Splitter::class),
                'splitDict',
                new ArgumentsNode([new VariableNode('resource'), new VariableNode('offset'), new VariableNode('length')]),
                static: true,
            ))),
        ];

        if ($node->type()->isNullable()) {
            $getBoundariesNodes[] = new IfNode(new BinaryNode('===', new PhpScalarNode(null), new VariableNode('boundaries')), [
                new ExpressionNode(new ReturnNode(new PhpScalarNode(null))),
            ]);
        }

        $propertyValueProvidersNodes = [];
        $propertiesClosuresNodes = [];

        foreach ($node->properties as $marshalledName => $property) {
            $propertyValueProvidersNodes = [
                ...$propertyValueProvidersNodes,
                ...($property['value'] instanceof ScalarNode ? [] : $this->providerNodes($property['value'], $context)),
            ];

            $propertyValueNode = $property['value'] instanceof ScalarNode
                ? $this->prepareScalarNode(
                    $property['value'],
                    new ArrayAccessNode(new VariableNode('b'), new PhpScalarNode(0)),
                    new ArrayAccessNode(new VariableNode('b'), new PhpScalarNode(1)),
                ) : new FunctionCallNode(
                    new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($property['value']->identifier())),
                    new ArgumentsNode([
                        new VariableNode('resource'),
                        new ArrayAccessNode(new VariableNode('b'), new PhpScalarNode(0)),
                        new ArrayAccessNode(new VariableNode('b'), new PhpScalarNode(1)),
                    ]),
                );

            $propertiesClosuresNodes[] = new IfNode(new BinaryNode('===', new PhpScalarNode($marshalledName), new VariableNode('k')), [
                new ExpressionNode(new AssignNode(
                    new ArrayAccessNode(new VariableNode('properties'), new PhpScalarNode($property['name'])),
                    new ClosureNode(new ParametersNode([]), 'mixed', true, [
                        new ExpressionNode(new ReturnNode(($property['formatter'])($propertyValueNode))),
                    ], new ArgumentsNode([
                        new VariableNode('resource'),
                        new VariableNode('b'),
                        new VariableNode('config'),
                        new VariableNode('instantiator'),
                        new VariableNode('providers', byReference: true),
                        new VariableNode('jsonDecodeFlags'),
                    ])),
                )),
                new ExpressionNode(new ContinueNode()),
            ]);
        }

        $fillPropertiesArrayNodes = [
            new ExpressionNode(new AssignNode(new VariableNode('properties'), new ArrayNode([]))),
            new ForEachNode(new VariableNode('boundaries'), new VariableNode('k'), new VariableNode('b'), $propertiesClosuresNodes),
        ];

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
                    new ParametersNode(['resource' => 'mixed', 'offset' => 'int', 'length' => 'int']),
                    ($node->type()->isNullable() ? '?' : '').$node->type()->className(),
                    true,
                    [...$getBoundariesNodes, ...$fillPropertiesArrayNodes, ...$instantiateNodes],
                    new ArgumentsNode([
                        new VariableNode('config'),
                        new VariableNode('instantiator'),
                        new VariableNode('providers', byReference: true),
                        new VariableNode('jsonDecodeFlags'),
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
                    new ParametersNode(['resource' => 'mixed', 'offset' => 'int', 'length' => 'int']),
                    'mixed',
                    true,
                    [new ExpressionNode(new ReturnNode($this->prepareScalarNode($node, new VariableNode('offset'), new VariableNode('length'))))],
                    new ArgumentsNode([
                        new VariableNode('jsonDecodeFlags'),
                    ]),
                ),
            )),
        ];
    }

    private function prepareScalarNode(ScalarNode $node, PhpNodeInterface $offsetNode, PhpNodeInterface $lengthNode): PhpNodeInterface
    {
        $accessor = new MethodCallNode(new PhpScalarNode('\\'.Decoder::class), 'decode', new ArgumentsNode([
            new VariableNode('resource'),
            $offsetNode,
            $lengthNode,
            new VariableNode('jsonDecodeFlags'),
        ]), static: true);

        if ($node->type()->isBackedEnum()) {
            return new MethodCallNode(
                new PhpScalarNode($node->type->className()),
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
