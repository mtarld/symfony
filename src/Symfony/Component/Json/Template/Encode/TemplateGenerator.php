<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json\Template\Encode;

use Symfony\Component\Encoder\DataModel\Encode\CollectionNode;
use Symfony\Component\Encoder\DataModel\Encode\DataModelNodeInterface;
use Symfony\Component\Encoder\DataModel\Encode\ObjectNode;
use Symfony\Component\Encoder\DataModel\Encode\ScalarNode;
use Symfony\Component\Encoder\Exception\LogicException;
use Symfony\Component\Encoder\Exception\RuntimeException;
use Symfony\Component\Encoder\VariableNameScoperTrait;
use Symfony\Component\Json\JsonEncoder;
use Symfony\Component\Json\Php\ArgumentsNode;
use Symfony\Component\Json\Php\ArrayAccessNode;
use Symfony\Component\Json\Php\AssignNode;
use Symfony\Component\Json\Php\BinaryNode;
use Symfony\Component\Json\Php\ExpressionNode;
use Symfony\Component\Json\Php\ForEachNode;
use Symfony\Component\Json\Php\FunctionCallNode;
use Symfony\Component\Json\Php\IfNode;
use Symfony\Component\Json\Php\PhpNodeInterface;
use Symfony\Component\Json\Php\PropertyNode;
use Symfony\Component\Json\Php\ScalarNode as PhpScalarNode;
use Symfony\Component\Json\Php\TemplateStringNode;
use Symfony\Component\Json\Php\VariableNode;
use Symfony\Component\Json\Template\TemplateGeneratorTrait;
use Symfony\Component\TypeInfo\Type;

/**
 * Generates a template PHP syntax tree that encodes data to JSON.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type JsonEncodeConfig from JsonEncoder
 */
final readonly class TemplateGenerator
{
    use TemplateGeneratorTrait;
    use VariableNameScoperTrait;

    /**
     * @param JsonEncodeConfig     $config
     * @param array<string, mixed> $context
     *
     * @return list<PhpNodeInterface>
     */
    public function generate(DataModelNodeInterface $node, array $config, array $context): array
    {
        $setupNodes = [];
        $accessor = $this->convertDataAccessorToPhpNode($node->getAccessor());

        if (true === ($context['root'] ?? true)) {
            $context['root'] = false;
            $setupNodes = [
                new ExpressionNode(new AssignNode(new VariableNode('jsonEncodeFlags'), new BinaryNode(
                    '??',
                    new ArrayAccessNode(new VariableNode('config'), new PhpScalarNode('json_encode_flags')),
                    new PhpScalarNode(0),
                ))),
            ];
        }

        if (false === ($context['for_stream'] ?? false) && !$this->isNodeAlteringJson($node)) {
            return [
                ...$setupNodes,
                new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([
                    new VariableNode('resource'),
                    $this->encodeValue($accessor),
                ]))),
            ];
        }

        if ($node instanceof CollectionNode) {
            $prefixName = $this->scopeVariableName('prefix', $context);

            if ($node->getType()->isList()) {
                $listNodes = [
                    new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode('[')]))),
                    new ExpressionNode(new AssignNode(new VariableNode($prefixName), new PhpScalarNode(''))),

                    new ForEachNode($accessor, null, $this->convertDataAccessorToPhpNode($node->item->accessor), [
                        new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new VariableNode($prefixName)]))),
                        ...$this->generate($node->item, $config, $context),
                        new ExpressionNode(new AssignNode(new VariableNode($prefixName), new PhpScalarNode(','))),
                    ]),

                    new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode(']')]))),
                ];

                if ($node->getType()->isNullable()) {
                    return [
                        ...$setupNodes,
                        new IfNode(new BinaryNode('===', new PhpScalarNode(null), $accessor), [
                            new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode('null')]))),
                        ], $listNodes),
                    ];
                }

                return [...$setupNodes, ...$listNodes];
            }

            $keyName = $this->scopeVariableName('key', $context);

            $dictNodes = [
                new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode('{')]))),
                new ExpressionNode(new AssignNode(new VariableNode($prefixName), new PhpScalarNode(''))),

                new ForEachNode($accessor, new VariableNode($keyName), $this->convertDataAccessorToPhpNode($node->item->accessor), [
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

            if ($node->getType()->isNullable()) {
                return [
                    ...$setupNodes,
                    new IfNode(new BinaryNode('===', new PhpScalarNode(null), $accessor), [
                        new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode('null')]))),
                    ], $dictNodes),
                ];
            }

            return [...$setupNodes, ...$dictNodes];
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

                $objectNodes = [
                    ...$objectNodes,
                    new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode($separator)]))),
                    new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode('"')]))),
                    new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode($encodedName)]))),
                    new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode('":')]))),
                    ...$this->generate($propertyNode, $config, $context),
                ];

                $separator = ',';
            }

            $objectNodes[] = new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode('}')])));

            if ($node->getType()->isNullable()) {
                return [
                    ...$setupNodes,
                    new IfNode(new BinaryNode('===', new PhpScalarNode(null), $accessor), [
                        new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode('null')]))),
                    ], $objectNodes),
                ];
            }

            return [...$setupNodes, ...$objectNodes];
        }

        if ($node instanceof ScalarNode) {
            $type = $node->getType();
            $scalarAccessor = $type->isBackedEnum() ? new PropertyNode($accessor, 'value', nullSafe: $type->isNullable()) : $accessor;
            $scalarNodes = [new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), $this->encodeValue($scalarAccessor)])))];

            if ($type->isNullable() && !$type->isBackedEnum() && !\in_array($type->getBuiltinType(), [Type::BUILTIN_TYPE_MIXED, Type::BUILTIN_TYPE_NULL], true)) {
                return [
                    ...$setupNodes,
                    new IfNode(new BinaryNode('===', new PhpScalarNode(null), $accessor), [
                        new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('resource'), new PhpScalarNode('null')]))),
                    ], $scalarNodes),
                ];
            }

            return [...$setupNodes, ...$scalarNodes];
        }

        throw new LogicException(sprintf('Unexpected "%s" node', $node::class));
    }

    private function encodeValue(PhpNodeInterface $node): PhpNodeInterface
    {
        return new FunctionCallNode('\json_encode', new ArgumentsNode([$node, new VariableNode('jsonEncodeFlags')]));
    }

    private function escapeString(PhpNodeInterface $node): PhpNodeInterface
    {
        return new FunctionCallNode('\substr', new ArgumentsNode([
            new FunctionCallNode('\json_encode', new ArgumentsNode([$node, new VariableNode('jsonEncodeFlags')])),
            new PhpScalarNode(1),
            new PhpScalarNode(-1),
        ]));
    }
}
