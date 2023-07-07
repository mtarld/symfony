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

use Symfony\Component\Serializer\Serialize\Configuration\Configuration;
use Symfony\Component\Serializer\Serialize\Dom\CollectionDomNode;
use Symfony\Component\Serializer\Serialize\Dom\DomNode;
use Symfony\Component\Serializer\Serialize\Dom\ObjectDomNode;
use Symfony\Component\Serializer\Serialize\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Serialize\Php\ArrayAccessNode;
use Symfony\Component\Serializer\Serialize\Php\ArrayNode;
use Symfony\Component\Serializer\Serialize\Php\ForEachNode;
use Symfony\Component\Serializer\Serialize\Php\AssignNode;
use Symfony\Component\Serializer\Serialize\Php\FunctionNode;
use Symfony\Component\Serializer\Serialize\Php\ExpressionNode;
use Symfony\Component\Serializer\Serialize\Php\VariableNode;
use Symfony\Component\Serializer\Serialize\Php\ScalarNode;
use Symfony\Component\Serializer\Serialize\Php\RawNode;
use Symfony\Component\Serializer\Type\TypeExtractorInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class NormalizerEncoderTemplateGenerator extends TemplateGenerator
{
    /**
     * @param class-string<EncoderInterface> $encoderClassName
     */
    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
        private readonly string $encoderClassName,
    ) {
    }

    protected function doGenerate(DomNode $domNode, Configuration $configuration, array $context): array
    {
        $context['nested'] ??= false;

        $accessor = new RawNode($domNode->accessor);
        $normalizedAccessor = $context['normalized_accessor'] ?? new VariableNode('normalized');

        if ($domNode instanceof CollectionDomNode) {
            $keyName = $this->scopeVariableName('key', $context);

            $nodes = [
                new ExpressionNode(new AssignNode($normalizedAccessor, new ArrayNode([]))),
                new ForEachNode($accessor, $keyName, substr($domNode->childrenDomNode->accessor, 1), [
                    ...$this->generate($domNode->childrenDomNode, $configuration, [
                        'normalized_accessor' => new ArrayAccessNode($normalizedAccessor, new VariableNode($keyName)),
                        'nested' => true,
                    ] + $context),
                ]),
            ];
        } elseif ($domNode instanceof ObjectDomNode) {
            $nodes = [];

            foreach ($domNode->properties as $name => $propertyDomNode) {
                array_push(
                    $nodes,
                    ...$this->generate($propertyDomNode, $configuration, [
                        'normalized_accessor' => new ArrayAccessNode($normalizedAccessor, new ScalarNode($name)),
                        'nested' => true,
                    ] + $context),
                );
            }
        } else {
            $nodes = [
                new ExpressionNode(new AssignNode($normalizedAccessor, $accessor)),
            ];
        }

        if (!$context['nested']) {
            $nodes[] = new ExpressionNode(new FunctionNode(sprintf('\\%s::encode', $this->encoderClassName), [
                new VariableNode('resource'),
                $normalizedAccessor,
                new VariableNode('configuration'),
            ]));
        }

        return $nodes;
    }
}
