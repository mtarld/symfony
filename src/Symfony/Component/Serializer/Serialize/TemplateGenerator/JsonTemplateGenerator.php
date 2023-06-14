<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\TemplateGenerator;

use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\ArrayAccessNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\AssignNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\BinaryNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\ExpressionNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\ForEachNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\FunctionNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\ScalarNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\TemplateStringNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\VariableNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\NodeInterface;
use Symfony\Component\Serializer\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class JsonTemplateGenerator extends TemplateGenerator
{
    protected function nullNodes(array $context): array
    {
        return [
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('null')])),
        ];
    }

    protected function scalarNodes(Type $type, NodeInterface $accessor, array $context): array
    {
        return [
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), $this->encodeValueNode($accessor)])),
        ];
    }

    protected function listNodes(Type $type, NodeInterface $accessor, array $context): array
    {
        $prefixName = $this->scopeVariableName('prefix', $context);
        $valueName = $this->scopeVariableName('value', $context);

        return [
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('[')])),
            new ExpressionNode(new AssignNode(new VariableNode($prefixName), new ScalarNode(''))),

            new ForEachNode($accessor, null, $valueName, [
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new VariableNode($prefixName)])),
                ...$this->generate($type->collectionValueType(), new VariableNode($valueName), $context),
                new ExpressionNode(new AssignNode(new VariableNode($prefixName), new ScalarNode(','))),
            ]),

            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode(']')])),
        ];
    }

    protected function dictNodes(Type $type, NodeInterface $accessor, array $context): array
    {
        $prefixName = $this->scopeVariableName('prefix', $context);
        $keyName = $this->scopeVariableName('key', $context);
        $valueName = $this->scopeVariableName('value', $context);

        return [
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('{')])),
            new ExpressionNode(new AssignNode(new VariableNode($prefixName), new ScalarNode(''))),

            new ForEachNode($accessor, $keyName, $valueName, [
                new ExpressionNode(new AssignNode(new VariableNode($keyName), $this->escapeStringNode(new VariableNode($keyName)))),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new TemplateStringNode(
                    new VariableNode($prefixName),
                    '"',
                    new VariableNode($keyName),
                    '":',
                )])),
                ...$this->generate($type->collectionValueType(), new VariableNode($valueName), $context),
                new ExpressionNode(new AssignNode(new VariableNode($prefixName), new ScalarNode(','))),
            ]),

            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('}')])),
        ];
    }

    protected function objectNodes(Type $type, array $properties, array $context): array
    {
        $nodes = [new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('{')]))];
        $separator = '';

        foreach ($properties as $property) {
            $encodedName = json_encode($property['name']);
            if (false === $encodedName) {
                throw new RuntimeException(sprintf('Cannot encode "%s"', $property['name']));
            }

            $encodedName = substr($encodedName, 1, -1);

            array_push(
                $nodes,
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($separator)])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('"')])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($encodedName)])),
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('":')])),
                ...$this->generate($property['type'], $property['accessor'], $context),
            );

            $separator = ',';
        }

        $nodes[] = new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('}')]));

        return $nodes;
    }

    protected function mixedNodes(NodeInterface $accessor, array $context): array
    {
        return [
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), $this->encodeValueNode($accessor)])),
        ];
    }

    private function encodeValueNode(NodeInterface $node): NodeInterface
    {
        return new FunctionNode('\json_encode', [
            $node,
            new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('json_encode_flags')), new ScalarNode(0)),
        ]);
    }

    private function escapeStringNode(NodeInterface $node): NodeInterface
    {
        return new FunctionNode('\substr', [
            new FunctionNode('\json_encode', [
                $node,
                new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('json_encode_flags')), new ScalarNode(0)),
            ]),
            new ScalarNode(1),
            new ScalarNode(-1),
        ]);
    }
}
