<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Marshal\Template;

use Symfony\Component\JsonMarshaller\Exception\LogicException;
use Symfony\Component\JsonMarshaller\Exception\RuntimeException;
use Symfony\Component\JsonMarshaller\Marshal\DataModel\CollectionNode;
use Symfony\Component\JsonMarshaller\Marshal\DataModel\DataModelNodeInterface;
use Symfony\Component\JsonMarshaller\Marshal\DataModel\ObjectNode;
use Symfony\Component\JsonMarshaller\Marshal\DataModel\ScalarNode;
use Symfony\Component\JsonMarshaller\Marshal\VariableNameScoperTrait;
use Symfony\Component\JsonMarshaller\Php\ArgumentsNode;
use Symfony\Component\JsonMarshaller\Php\ArrayAccessNode;
use Symfony\Component\JsonMarshaller\Php\AssignNode;
use Symfony\Component\JsonMarshaller\Php\BinaryNode;
use Symfony\Component\JsonMarshaller\Php\ExpressionNode;
use Symfony\Component\JsonMarshaller\Php\ForEachNode;
use Symfony\Component\JsonMarshaller\Php\FunctionCallNode;
use Symfony\Component\JsonMarshaller\Php\IfNode;
use Symfony\Component\JsonMarshaller\Php\PhpNodeInterface;
use Symfony\Component\JsonMarshaller\Php\PropertyNode;
use Symfony\Component\JsonMarshaller\Php\ScalarNode as PhpScalarNode;
use Symfony\Component\JsonMarshaller\Php\TemplateStringNode;
use Symfony\Component\JsonMarshaller\Php\VariableNode;

/**
 * Generates a template PHP syntax tree that marshalles data to JSON.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class TemplateGenerator
{
    use VariableNameScoperTrait;

    // TODO
    public function generate(DataModelNodeInterface $node, array $config, array $context): array
    {
        if ($node instanceof CollectionNode) {
            $prefixName = $this->scopeVariableName('prefix', $context);

            if ($node->type->isList()) {
                $listNodes = [
                    new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode('[')]))),
                    new ExpressionNode(new AssignNode(new VariableNode($prefixName), new PhpScalarNode(''))),

                    new ForEachNode($node->accessor, null, $node->item->accessor, [
                        new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new VariableNode($prefixName)]))),
                        ...$this->generate($node->item, $config, $context),
                        new ExpressionNode(new AssignNode(new VariableNode($prefixName), new PhpScalarNode(','))),
                    ]),

                    new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode(']')]))),
                ];

                if ($node->type->isNullable()) {
                    return [
                        new IfNode(new BinaryNode('===', new PhpScalarNode(null), $node->accessor), [
                            new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode('null')]))),
                        ], $listNodes),
                    ];
                }

                return $listNodes;
            }

            $keyName = $this->scopeVariableName('key', $context);

            $dictNodes = [
                new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode('{')]))),
                new ExpressionNode(new AssignNode(new VariableNode($prefixName), new PhpScalarNode(''))),

                new ForEachNode($node->accessor, new VariableNode($keyName), $node->item->accessor, [
                    new ExpressionNode(new AssignNode(new VariableNode($keyName), $this->escapeString(new VariableNode($keyName)))),
                    new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([
                        new VariableNode('resource'),
                        new TemplateStringNode(new VariableNode($prefixName), '"', new VariableNode($keyName), '":'),
                    ]))),
                    ...$this->generate($node->item, $config, $context),
                    new ExpressionNode(new AssignNode(new VariableNode($prefixName), new PhpScalarNode(','))),
                ]),

                new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode('}')]))),
            ];

            if ($node->type->isNullable()) {
                return [
                    new IfNode(new BinaryNode('===', new PhpScalarNode(null), $node->accessor), [
                        new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode('null')]))),
                    ], $dictNodes),
                ];
            }

            return $dictNodes;
        }

        if ($node instanceof ObjectNode) {
            $objectNodes = [new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode('{')])))];
            $separator = '';

            foreach ($node->properties as $name => $propertyNode) {
                $encodedName = json_encode($name);
                if (false === $encodedName) {
                    throw new RuntimeException(sprintf('Cannot encode "%s"', $name));
                }

                $encodedName = substr($encodedName, 1, -1);

                array_push(
                    $objectNodes,
                    new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode($separator)]))),
                    new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode('"')]))),
                    new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode($encodedName)]))),
                    new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode('":')]))),
                    ...$this->generate($propertyNode, $config, $context),
                );

                $separator = ',';
            }

            $objectNodes[] = new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode('}')])));

            if ($node->type->isNullable()) {
                return [
                    new IfNode(new BinaryNode('===', new PhpScalarNode(null), $node->accessor), [
                        new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode('null')]))),
                    ], $objectNodes),
                ];
            }

            return $objectNodes;
        }

        if ($node instanceof ScalarNode) {
            $scalarAccessor = $node->type->isBackedEnum() ? new PropertyNode($node->accessor, 'value') : $node->accessor;
            $scalarNodes = [new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), $this->encodeValue($scalarAccessor)])))];

            if ($node->type->isNullable()) {
                return [
                    new IfNode(new BinaryNode('===', new PhpScalarNode(null), $node->accessor), [
                        new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode('null')]))),
                    ], $scalarNodes),
                ];
            }

            return $scalarNodes;
        }

        throw new LogicException(sprintf('Unexpected "%s" node', $node::class));
    }

    private function encodeValue(PhpNodeInterface $node): PhpNodeInterface
    {
        return new FunctionCallNode('\json_encode', new ArgumentsNode([
            $node,
            // TODO store it in a var?
            new ArrayAccessNode(new VariableNode('config'), new PhpScalarNode('json_encode_flags')),
        ]));
    }

    private function escapeString(PhpNodeInterface $node): PhpNodeInterface
    {
        return new FunctionCallNode('\substr', new ArgumentsNode([
            new FunctionCallNode('\json_encode', new ArgumentsNode([
                $node,
                new ArrayAccessNode(new VariableNode('config'), new PhpScalarNode('json_encode_flags')),
            ])),
            new PhpScalarNode(1),
            new PhpScalarNode(-1),
        ]));
    }
}
