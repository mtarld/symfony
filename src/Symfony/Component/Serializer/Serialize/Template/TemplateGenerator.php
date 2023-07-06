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
use Symfony\Component\Serializer\Serialize\Dom\UnionDomNode;
use Symfony\Component\Serializer\Serialize\Php\BinaryNode;
use Symfony\Component\Serializer\Serialize\Php\FunctionNode;
use Symfony\Component\Serializer\Serialize\Php\NodeInterface;
use Symfony\Component\Serializer\Serialize\Php\UnaryNode;
use Symfony\Component\Serializer\Serialize\Php\IfNode;
use Symfony\Component\Serializer\Serialize\Php\ScalarNode;
use Symfony\Component\Serializer\Serialize\Php\RawNode;
use Symfony\Component\Serializer\Serialize\VariableNameScoperTrait;
use Symfony\Component\Serializer\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
abstract class TemplateGenerator implements TemplateGeneratorInterface
{
    use VariableNameScoperTrait;

    /**
     * @param array<string, mixed> $runtime
     *
     * @return list<NodeInterface>
     */
    abstract protected function doGenerate(DomNode $domNode, Configuration $configuration, array $runtime): array;

    final public function generate(DomNode $domNode, Configuration $configuration, array $runtime): array
    {
        if ($domNode instanceof UnionDomNode) {
            $domNodes = $domNode->domNodes;

            if (1 === \count($domNodes)) {
                return $this->generate($domNodes[0], $configuration, $runtime);
            }

            /** @var Type $ifType */
            $ifDomNode = array_shift($domNodes);

            /** @var Type $elseType */
            $elseDomNode = array_pop($domNodes);

            return [new IfNode(
                $this->typeValidator($ifDomNode),
                $this->generate($ifDomNode, $configuration, $runtime),
                $this->generate($elseDomNode, $configuration, $runtime),
                array_map(fn (DomNode $n): array => [
                    'condition' => $this->typeValidator($n),
                    'body' => $this->generate($n, $configuration, $runtime),
                ], $domNodes),
            )];
        }

        return $this->doGenerate($domNode, $configuration, $runtime);
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
}
