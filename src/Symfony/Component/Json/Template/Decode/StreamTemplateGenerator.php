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
use Symfony\Component\Json\Php\ContinueNode;
use Symfony\Component\Json\Php\ExpressionNode;
use Symfony\Component\Json\Php\ForEachNode;
use Symfony\Component\Json\Php\FunctionCallNode;
use Symfony\Component\Json\Php\IfNode;
use Symfony\Component\Json\Php\MethodCallNode;
use Symfony\Component\Json\Php\ParametersNode;
use Symfony\Component\Json\Php\PhpNodeInterface;
use Symfony\Component\Json\Php\ReturnNode;
use Symfony\Component\Json\Php\ScalarNode as PhpScalarNode;
use Symfony\Component\Json\Php\VariableNode;
use Symfony\Component\Json\Php\YieldNode;
use Symfony\Component\Json\Template\PhpNodeDataAccessor;
use Symfony\Component\Json\Template\TemplateGeneratorTrait;

/**
 * Generates a template PHP syntax tree that decodes data lazily.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type JsonDecodeConfig from JsonDecoder
 */
final readonly class StreamTemplateGenerator
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
            new ExpressionNode(new AssignNode(new VariableNode('jsonDecodeFlags'), new BinaryNode(
                '??',
                new ArrayAccessNode(new VariableNode('config'), new PhpScalarNode('json_decode_flags')),
                new PhpScalarNode(0),
            ))),
            ...$this->getProviderNodes($node, $context),
            new ExpressionNode(new ReturnNode(new FunctionCallNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->getIdentifier())),
                new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode(0), new PhpScalarNode(-1)]),
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
            $node instanceof CollectionNode => $this->getCollectionNodes($node, $context),
            $node instanceof ObjectNode => $this->getObjectNodes($node, $context),
            $node instanceof ScalarNode => $this->getScalarNodes($node, $context),
            default => throw new LogicException(sprintf('Unexpected "%s" node', $node::class)),
        };
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    private function getCollectionNodes(CollectionNode $node, array &$context): array
    {
        $getBoundariesNodes = [
            new ExpressionNode(new AssignNode(new VariableNode('boundaries'), new MethodCallNode(
                new PhpScalarNode('\\'.Splitter::class),
                $node->getType()->isList() ? 'splitList' : 'splitDict',
                new ArgumentsNode([new VariableNode('resource'), new VariableNode('offset'), new VariableNode('length')]),
                static: true,
            ))),
        ];

        if ($node->getType()->isNullable()) {
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
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->item->getIdentifier())),
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
                    new VariableNode('services'),
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
                'array' === $node->getType()->getBuiltinType() ? new FunctionCallNode('\iterator_to_array', new ArgumentsNode([$iterableValueNode])) : $iterableValueNode,
            )),
        ];

        $providerNodes = $node->item instanceof ScalarNode ? [] : $this->getProviderNodes($node->item, $context);

        return [
            new ExpressionNode(new AssignNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->getIdentifier())),
                new ClosureNode(
                    new ParametersNode(['resource' => 'mixed', 'offset' => 'int', 'length' => 'int']),
                    ($node->getType()->isNullable() ? '?' : '').$node->getType()->getBuiltinType(),
                    true,
                    [...$getBoundariesNodes, ...$iterableClosureNodes, ...$returnNodes],
                    new ArgumentsNode([
                        new VariableNode('config'),
                        new VariableNode('instantiator'),
                        new VariableNode('services'),
                        new VariableNode('providers', byReference: true),
                        new VariableNode('jsonDecodeFlags'),
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

        $getBoundariesNodes = [
            new ExpressionNode(new AssignNode(new VariableNode('boundaries'), new MethodCallNode(
                new PhpScalarNode('\\'.Splitter::class),
                'splitDict',
                new ArgumentsNode([new VariableNode('resource'), new VariableNode('offset'), new VariableNode('length')]),
                static: true,
            ))),
        ];

        if ($node->getType()->isNullable()) {
            $getBoundariesNodes[] = new IfNode(new BinaryNode('===', new PhpScalarNode(null), new VariableNode('boundaries')), [
                new ExpressionNode(new ReturnNode(new PhpScalarNode(null))),
            ]);
        }

        $propertyValueProvidersNodes = [];
        $propertiesClosuresNodes = [];

        foreach ($node->properties as $encodedName => $property) {
            $propertyValueProvidersNodes = [
                ...$propertyValueProvidersNodes,
                ...($property['value'] instanceof ScalarNode ? [] : $this->getProviderNodes($property['value'], $context)),
            ];

            $propertyValueNode = $property['value'] instanceof ScalarNode
                ? $this->prepareScalarNode(
                    $property['value'],
                    new ArrayAccessNode(new VariableNode('b'), new PhpScalarNode(0)),
                    new ArrayAccessNode(new VariableNode('b'), new PhpScalarNode(1)),
                ) : new FunctionCallNode(
                    new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($property['value']->getIdentifier())),
                    new ArgumentsNode([
                        new VariableNode('resource'),
                        new ArrayAccessNode(new VariableNode('b'), new PhpScalarNode(0)),
                        new ArrayAccessNode(new VariableNode('b'), new PhpScalarNode(1)),
                    ]),
                );

            $propertiesClosuresNodes[] = new IfNode(new BinaryNode('===', new PhpScalarNode($encodedName), new VariableNode('k')), [
                new ExpressionNode(new AssignNode(
                    new ArrayAccessNode(new VariableNode('properties'), new PhpScalarNode($property['name'])),
                    new ClosureNode(new ParametersNode([]), 'mixed', true, [
                        new ExpressionNode(new ReturnNode(
                            $this->convertDataAccessorToPhpNode($property['accessor'](new PhpNodeDataAccessor($propertyValueNode)))
                        )),
                    ], new ArgumentsNode([
                        new VariableNode('resource'),
                        new VariableNode('b'),
                        new VariableNode('config'),
                        new VariableNode('instantiator'),
                        new VariableNode('services'),
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
                new ArgumentsNode([new PhpScalarNode($node->getType()->getClassName()), new VariableNode('properties')]),
            ))),
        ];

        return [
            new ExpressionNode(new AssignNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->getIdentifier())),
                new ClosureNode(
                    new ParametersNode(['resource' => 'mixed', 'offset' => 'int', 'length' => 'int']),
                    ($node->getType()->isNullable() ? '?' : '').$node->getType()->getClassName(),
                    true,
                    [...$getBoundariesNodes, ...$fillPropertiesArrayNodes, ...$instantiateNodes],
                    new ArgumentsNode([
                        new VariableNode('config'),
                        new VariableNode('instantiator'),
                        new VariableNode('services'),
                        new VariableNode('providers', byReference: true),
                        new VariableNode('jsonDecodeFlags'),
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
        return [
            new ExpressionNode(new AssignNode(
                new ArrayAccessNode(new VariableNode('providers'), new PhpScalarNode($node->getIdentifier())),
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

        if ($node->getType()->isBackedEnum()) {
            return new MethodCallNode(
                new PhpScalarNode($node->getType()->getClassName()),
                $node->getType()->isNullable() ? 'tryFrom' : 'from',
                new ArgumentsNode([$accessor]),
                static: true,
            );
        }

        if ($node->getType()->isObject()) {
            return new CastNode('object', $accessor);
        }

        return $accessor;
    }
}
