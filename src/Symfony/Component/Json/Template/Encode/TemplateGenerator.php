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
use Symfony\Component\Json\Php\MethodCallNode;
use Symfony\Component\Json\Php\PhpNodeInterface;
use Symfony\Component\Json\Php\PropertyNode;
use Symfony\Component\Json\Php\ScalarNode as PhpScalarNode;
use Symfony\Component\Json\Php\TemplateStringNode;
use Symfony\Component\Json\Php\VariableNode;
use Symfony\Component\Json\Php\YieldNode;
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
                new ExpressionNode(new AssignNode(new VariableNode('flags'), new BinaryNode(
                    '??',
                    new ArrayAccessNode(new VariableNode('config'), new PhpScalarNode('json_encode_flags')),
                    new PhpScalarNode(0),
                ))),
            ];
        }

        if (!$this->isNodeAlteringJson($node)) {
            return [
                ...$setupNodes,
                $this->yieldJson($this->encodeValue($accessor), $context),
            ];
        }

        if ($node instanceof CollectionNode) {
            $prefixName = $this->scopeVariableName('prefix', $context);

            if ($node->getType()->isList()) {
                $listNodes = [
                    $this->yieldJson(new PhpScalarNode('['), $context),
                    new ExpressionNode(new AssignNode(new VariableNode($prefixName), new PhpScalarNode(''))),

                    new ForEachNode($accessor, null, $this->convertDataAccessorToPhpNode($node->item->accessor), [
                        $this->yieldJson(new VariableNode($prefixName), $context),
                        ...$this->generate($node->item, $config, $context),
                        new ExpressionNode(new AssignNode(new VariableNode($prefixName), new PhpScalarNode(','))),
                    ]),

                    $this->yieldJson(new PhpScalarNode(']'), $context),
                ];

                if ($node->getType()->isNullable()) {
                    return [
                        ...$setupNodes,
                        new IfNode(new BinaryNode('===', new PhpScalarNode(null), $accessor), [
                            $this->yieldJson(new PhpScalarNode('null'), $context),
                        ], $listNodes),
                    ];
                }

                return [...$setupNodes, ...$listNodes];
            }

            $keyName = $this->scopeVariableName('key', $context);

            $dictNodes = [
                $this->yieldJson(new PhpScalarNode('{'), $context),
                new ExpressionNode(new AssignNode(new VariableNode($prefixName), new PhpScalarNode(''))),
                new ForEachNode($accessor, new VariableNode($keyName), $this->convertDataAccessorToPhpNode($node->item->accessor), [
                    new ExpressionNode(new AssignNode(new VariableNode($keyName), $this->escapeString(new VariableNode($keyName)))),
                    $this->yieldJson(new TemplateStringNode(new VariableNode($prefixName), '"', new VariableNode($keyName), '":'), $context),
                    ...$this->generate($node->item, $config, $context),
                    new ExpressionNode(new AssignNode(new VariableNode($prefixName), new PhpScalarNode(','))),
                ]),
                $this->yieldJson(new PhpScalarNode('}'), $context),
            ];

            if ($node->getType()->isNullable()) {
                return [
                    ...$setupNodes,
                    new IfNode(new BinaryNode('===', new PhpScalarNode(null), $accessor), [
                        $this->yieldJson(new PhpScalarNode('null'), $context),
                    ], $dictNodes),
                ];
            }

            return [...$setupNodes, ...$dictNodes];
        }

        if ($node instanceof ObjectNode) {
            $objectNodes = [$this->yieldJson(new PhpScalarNode('{'), $context)];
            $separator = '';

            foreach ($node->properties as $name => $propertyNode) {
                $encodedName = json_encode($name);
                if (false === $encodedName) {
                    throw new RuntimeException(sprintf('Cannot encode "%s"', $name));
                }

                $encodedName = substr($encodedName, 1, -1);

                $objectNodes = [
                    ...$objectNodes,
                    $this->yieldJson(new PhpScalarNode($separator), $context),
                    $this->yieldJson(new PhpScalarNode('"'), $context),
                    $this->yieldJson(new PhpScalarNode($encodedName), $context),
                    $this->yieldJson(new PhpScalarNode('":'), $context),
                    ...$this->generate($propertyNode, $config, $context),
                ];

                $separator = ',';
            }

            $objectNodes[] = $this->yieldJson(new PhpScalarNode('}'), $context);

            if ($node->getType()->isNullable()) {
                return [
                    ...$setupNodes,
                    new IfNode(new BinaryNode('===', new PhpScalarNode(null), $accessor), [
                        $this->yieldJson(new PhpScalarNode('null'), $context),
                    ], $objectNodes),
                ];
            }

            return [...$setupNodes, ...$objectNodes];
        }

        if ($node instanceof ScalarNode) {
            $type = $node->getType();
            $scalarAccessor = $type->isBackedEnum() ? new PropertyNode($accessor, 'value', nullSafe: $type->isNullable()) : $accessor;
            $scalarNodes = [$this->yieldJson($this->encodeValue($scalarAccessor), $context)];

            if ($type->isNullable() && !$type->isBackedEnum() && !\in_array($type->getBuiltinType(), [Type::BUILTIN_TYPE_MIXED, Type::BUILTIN_TYPE_NULL], true)) {
                return [
                    ...$setupNodes,
                    new IfNode(new BinaryNode('===', new PhpScalarNode(null), $accessor), [
                        $this->yieldJson(new PhpScalarNode('null'), $context),
                    ], $scalarNodes),
                ];
            }

            return [...$setupNodes, ...$scalarNodes];
        }

        throw new LogicException(sprintf('Unexpected "%s" node', $node::class));
    }

    private function encodeValue(PhpNodeInterface $node): PhpNodeInterface
    {
        return new FunctionCallNode('\json_encode', new ArgumentsNode([$node, new VariableNode('flags')]));
    }

    private function escapeString(PhpNodeInterface $node): PhpNodeInterface
    {
        return new FunctionCallNode('\substr', new ArgumentsNode([
            new FunctionCallNode('\json_encode', new ArgumentsNode([$node, new VariableNode('flags')])),
            new PhpScalarNode(1),
            new PhpScalarNode(-1),
        ]));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function yieldJson(PhpNodeInterface $json, array $context): PhpNodeInterface
    {
        return match ($context['stream_type']) {
            'resource' => new ExpressionNode(new FunctionCallNode('\fwrite', new ArgumentsNode([new VariableNode('stream'), $json]))),
            'stream' => new ExpressionNode(new MethodCallNode(new VariableNode('stream'), 'write', new ArgumentsNode([$json]))),
            default => new ExpressionNode(new YieldNode($json)),
        };
    }
}
