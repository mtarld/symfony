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
use Symfony\Component\Serializer\Serialize\Php\ForEachNode;
use Symfony\Component\Serializer\Serialize\Php\AssignNode;
use Symfony\Component\Serializer\Serialize\Php\FunctionNode;
use Symfony\Component\Serializer\Serialize\Php\TemplateStringNode;
use Symfony\Component\Serializer\Serialize\Php\ExpressionNode;
use Symfony\Component\Serializer\Serialize\Php\IfNode;
use Symfony\Component\Serializer\Serialize\Php\VariableNode;
use Symfony\Component\Serializer\Serialize\Php\ScalarNode;
use Symfony\Component\Serializer\Serialize\Php\RawNode;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class CsvTemplateGenerator extends TemplateGenerator
{
    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
    }

    public function doGenerate(DomNode $domNode, array $context): array
    {
        throw new \RuntimeException('Not implemented yet.');
        $accessor = new RawNode($domNode->accessor);

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
}
