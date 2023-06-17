<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Template;

use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Serialize\Dom\CollectionDomNode;
use Symfony\Component\Serializer\Serialize\Dom\DomNode;
use Symfony\Component\Serializer\Serialize\Dom\ObjectDomNode;
use Symfony\Component\Serializer\Serialize\Dom\UnionDomNode;
use Symfony\Component\Serializer\Serialize\Php\ArrayAccessNode;
use Symfony\Component\Serializer\Serialize\Php\BinaryNode;
use Symfony\Component\Serializer\Serialize\Php\ForEachNode;
use Symfony\Component\Serializer\Serialize\Php\AssignNode;
use Symfony\Component\Serializer\Serialize\Php\FunctionNode;
use Symfony\Component\Serializer\Serialize\Php\NodeInterface;
use Symfony\Component\Serializer\Serialize\Php\TemplateStringNode;
use Symfony\Component\Serializer\Serialize\Php\ExpressionNode;
use Symfony\Component\Serializer\Serialize\Php\UnaryNode;
use Symfony\Component\Serializer\Serialize\Php\IfNode;
use Symfony\Component\Serializer\Serialize\Php\VariableNode;
use Symfony\Component\Serializer\Serialize\Php\ScalarNode;
use Symfony\Component\Serializer\Serialize\Php\RawNode;
use Symfony\Component\Serializer\Serialize\VariableNameScoperTrait;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class JsonTemplateGenerator implements TemplateGeneratorInterface
{
    use VariableNameScoperTrait;

    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
    }

    public function generate(DomNode $domNode, array $context): array
    {
        $accessor = new RawNode($domNode->accessor);

        if ($domNode instanceof UnionDomNode) {
            $domNodes = $domNode->domNodes;

            if (1 === \count($domNodes)) {
                return $this->generate($domNodes[0], $context);
            }

            /** @var Type $ifType */
            $ifDomNode = array_shift($domNodes);

            /** @var Type $elseType */
            $elseDomNode = array_pop($domNodes);

            return [new IfNode(
                $this->typeValidator($ifDomNode),
                $this->generate($ifDomNode, $context),
                $this->generate($elseDomNode, $context),
                array_map(fn (DomNode $n): array => [
                    'condition' => $this->typeValidator($n),
                    'body' => $this->generate($n, $context),
                ], $domNodes),
            )];
        }

        if ($domNode instanceof CollectionDomNode) {
            $prefixName = $this->scopeVariableName('prefix', $context);

            if ($domNode->isList) {
                return [
                    new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('[')])),
                    new ExpressionNode(new AssignNode(new VariableNode($prefixName), new ScalarNode(''))),

                    new ForEachNode($accessor, null, substr($domNode->childrenDomNode->accessor, 1), [
                        new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new VariableNode($prefixName)])),
                        ...$this->generate($domNode->childrenDomNode, $context),
                        new ExpressionNode(new AssignNode(new VariableNode($prefixName), new ScalarNode(','))),
                    ]),

                    new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode(']')])),
                ];
            }

            $keyName = $this->scopeVariableName('key', $context);

            return [
                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('{')])),
                new ExpressionNode(new AssignNode(new VariableNode($prefixName), new ScalarNode(''))),

                new ForEachNode($accessor, $keyName, substr($domNode->childrenDomNode->accessor, 1), [
                    new ExpressionNode(new AssignNode(new VariableNode($keyName), $this->escapeString(new VariableNode($keyName)))),
                    new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new TemplateStringNode(
                        new VariableNode($prefixName),
                        '"',
                        new VariableNode($keyName),
                        '":',
                    )])),
                    ...$this->generate($domNode->childrenDomNode, $context),
                    new ExpressionNode(new AssignNode(new VariableNode($prefixName), new ScalarNode(','))),
                ]),

                new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('}')])),
            ];
        }

        if ($domNode instanceof ObjectDomNode) {
            $nodes = [new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('{')]))];
            $separator = '';

            foreach ($domNode->properties as $name => $propertyDomNode) {
                $encodedName = json_encode($name);
                if (false === $encodedName) {
                    throw new RuntimeException(sprintf('Cannot encode "%s"', $name));
                }

                $encodedName = substr($encodedName, 1, -1);

                array_push(
                    $nodes,
                    new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($separator)])),
                    new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('"')])),
                    new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode($encodedName)])),
                    new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('":')])),
                    ...$this->generate($propertyDomNode, $context),
                );

                $separator = ',';
            }

            $nodes[] = new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), new ScalarNode('}')]));

            return $nodes;
        }

        return [
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), $this->encodeValue($accessor)])),
        ];
    }

    private function typeValidator(DomNode $domNode): NodeInterface
    {
        $accessor = new RawNode($domNode->accessor);

        if ($domNode instanceof CollectionDomNode) {
            if ($domNode->isArray) {
                return $domNode->isList
                    ? new BinaryNode('&&', new FunctionNode('\is_array', [$accessor]), new FunctionNode('\array_is_list', [$accessor]))
                    : new BinaryNode('&&', new FunctionNode('\is_array', [$accessor]), new UnaryNode('!', new FunctionNode('\array_is_list', [$accessor])));
            }

            return new FunctionNode('\is_iterable', [$accessor]);
        }

        if ($domNode instanceof ObjectDomNode) {
            return new BinaryNode('instanceof', $accessor, new ScalarNode($domNode->className));
        }

        return new FunctionNode(sprintf('\is_%s', $domNode->type), [$accessor]);
    }

    private function encodeValue(NodeInterface $node): NodeInterface
    {
        return new FunctionNode('\json_encode', [
            $node,
            new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('json_encode_flags')), new ScalarNode(0)),
        ]);
    }

    private function escapeString(NodeInterface $node): NodeInterface
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
