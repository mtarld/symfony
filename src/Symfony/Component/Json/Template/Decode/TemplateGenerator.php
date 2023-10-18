<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json\Template\Decode;

use Symfony\Component\Encoder\DataModel\Decode\CollectionNode;
use Symfony\Component\Encoder\DataModel\Decode\DataModelNodeInterface;
use Symfony\Component\Encoder\DataModel\Decode\ObjectNode;
use Symfony\Component\Encoder\DataModel\Decode\ScalarNode;
use Symfony\Component\Encoder\Exception\LogicException;
use Symfony\Component\Json\JsonDecoder;
use Symfony\Component\Json\Php\ArgumentsNode;
use Symfony\Component\Json\Php\ArrayAccessNode;
use Symfony\Component\Json\Php\ArrayNode;
use Symfony\Component\Json\Php\AssignNode;
use Symfony\Component\Json\Php\BinaryNode;
use Symfony\Component\Json\Php\CastNode;
use Symfony\Component\Json\Php\ClosureNode;
use Symfony\Component\Json\Php\ExpressionNode;
use Symfony\Component\Json\Php\ForEachNode;
use Symfony\Component\Json\Php\FunctionCallNode;
use Symfony\Component\Json\Php\IfNode;
use Symfony\Component\Json\Php\MethodCallNode;
use Symfony\Component\Json\Php\ParametersNode;
use Symfony\Component\Json\Php\PhpNodeInterface;
use Symfony\Component\Json\Php\ReturnNode;
use Symfony\Component\Json\Php\ScalarNode as PhpScalarNode;
use Symfony\Component\Json\Php\TernaryConditionNode;
use Symfony\Component\Json\Php\VariableNode;
use Symfony\Component\Json\Php\YieldNode;
use Symfony\Component\Json\Template\PhpNodeDataAccessor;
use Symfony\Component\Json\Template\TemplateGeneratorTrait;

/**
 * Generates a template PHP syntax tree that decodes data.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type JsonDecodeConfig from JsonDecoder
 */
final readonly class TemplateGenerator
{
    use TemplateGeneratorTrait;

    /**
     * @param JsonDecodeConfig     $config
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    public function generate(DataModelNodeInterface $node, array $config, array $context): array
    {
        return [
            new ExpressionNode(new AssignNode(new VariableNode('flags'), new BinaryNode(
                '??',
                new ArrayAccessNode(new VariableNode('config'), new PhpScalarNode('json_decode_flags')),
                new PhpScalarNode(0),
            ))),
            ...$this->getProviderNodes($node, $context),
            new ExpressionNode(new ReturnNode(new FunctionCallNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->getIdentifier())),
                new ArgumentsNode([
                    new MethodCallNode(new PhpScalarNode('\\'.Decoder::class), 'decodeString', new ArgumentsNode([
                        new VariableNode('string'),
                        new VariableNode('flags'),
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
    private function getProviderNodes(DataModelNodeInterface $node, array &$context): array
    {
        if ($context['providers'][$node->getIdentifier()] ?? false) {
            return [];
        }

        $context['providers'][$node->getIdentifier()] = true;

        return match (true) {
            !$this->isNodeAlteringJson($node) => $this->getRawJsonNodes($node),
            $node instanceof CollectionNode => $this->getCollectionNodes($node, $context),
            $node instanceof ObjectNode => $this->getObjectNodes($node, $context),
            $node instanceof ScalarNode => $this->getScalarNodes($node),
            default => throw new LogicException(sprintf('Unexpected "%s" node', $node::class)),
        };
    }

    /**
     * @return list<PhpNodeInterface>
     */
    private function getRawJsonNodes(DataModelNodeInterface $node): array
    {
        return [
            new ExpressionNode(new AssignNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->getIdentifier())),
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
    private function getCollectionNodes(CollectionNode $node, array &$context): array
    {
        $returnNullNodes = $node->getType()->isNullable() ? [
            new IfNode(new BinaryNode('===', new PhpScalarNode(null), new VariableNode('data')), [
                new ExpressionNode(new ReturnNode(new PhpScalarNode(null))),
            ]),
        ] : [];

        $itemValueNode = $this->isNodeAlteringJson($node->item)
            ? new FunctionCallNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->item->getIdentifier())),
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
                'array' === $node->getType()->getBuiltinType() ? new FunctionCallNode('\iterator_to_array', new ArgumentsNode([$iterableValueNode])) : $iterableValueNode,
            )),
        ];

        $providerNodes = $this->isNodeAlteringJson($node->item) ? $this->getProviderNodes($node->item, $context) : [];

        return [
            new ExpressionNode(new AssignNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->getIdentifier())),
                new ClosureNode(
                    new ParametersNode(['data' => '?iterable']),
                    ($node->getType()->isNullable() ? '?' : '').$node->getType()->getBuiltinType(),
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
    private function getObjectNodes(ObjectNode $node, array &$context): array
    {
        if ($node->ghost) {
            return [];
        }

        $returnNullNodes = $node->getType()->isNullable() ? [
            new IfNode(new BinaryNode('===', new PhpScalarNode(null), new VariableNode('data')), [
                new ExpressionNode(new ReturnNode(new PhpScalarNode(null))),
            ]),
        ] : [];

        $propertyValueProvidersNodes = [];
        $propertiesValues = [];

        foreach ($node->properties as $encodedName => $property) {
            $propertyValueProvidersNodes = [
                ...$propertyValueProvidersNodes,
                ...($this->isNodeAlteringJson($property['value']) ? $this->getProviderNodes($property['value'], $context) : []),
            ];

            $propertyValueNode = $this->isNodeAlteringJson($property['value'])
                ? new TernaryConditionNode(
                    new FunctionCallNode('\array_key_exists', new ArgumentsNode([new PhpScalarNode($encodedName), new VariableNode('data')])),
                    new FunctionCallNode(
                        new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($property['value']->getIdentifier())),
                        new ArgumentsNode([new ArrayAccessNode(new VariableNode('data'), new PhpScalarNode($encodedName))]),
                    ),
                    new PhpScalarNode('_symfony_missing_value'),
                )
                : new BinaryNode('??', new ArrayAccessNode(new VariableNode('data'), new PhpScalarNode($encodedName)), new PhpScalarNode('_symfony_missing_value'));

            $propertiesValues[$property['name']] = $this->convertDataAccessorToPhpNode($property['accessor'](new PhpNodeDataAccessor($propertyValueNode)));
        }

        return [
            new ExpressionNode(new AssignNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->getIdentifier())),
                new ClosureNode(
                    new ParametersNode(['data' => '?array']),
                    ($node->getType()->isNullable() ? '?' : '').$node->getType()->getClassName(),
                    true,
                    [
                        ...$returnNullNodes,
                        new ExpressionNode(new ReturnNode(new MethodCallNode(
                            new VariableNode('instantiator'),
                            'instantiate',
                            new ArgumentsNode([
                                new PhpScalarNode($node->getType()->getClassName()),
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
    private function getScalarNodes(ScalarNode $node): array
    {
        $accessor = match (true) {
            $node->getType()->isBackedEnum() => new MethodCallNode(
                new PhpScalarNode($node->getType()->getClassName()),
                $node->getType()->isNullable() ? 'tryFrom' : 'from',
                new ArgumentsNode([new VariableNode('data')]),
                static: true,
            ),
            $node->getType()->isObject() => new CastNode('object', new VariableNode('data')),
            default => new VariableNode('data'),
        };

        return [
            new ExpressionNode(new AssignNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->getIdentifier())),
                new ClosureNode(
                    new ParametersNode(['data' => 'mixed']),
                    'mixed',
                    true,
                    [new ExpressionNode(new ReturnNode($accessor))],
                ),
            )),
        ];
    }
}
