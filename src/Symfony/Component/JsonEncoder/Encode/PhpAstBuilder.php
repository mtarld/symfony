<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Encode;

use PhpParser\BuilderFactory;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\Encapsed;
use PhpParser\Node\Scalar\EncapsedStringPart;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ElseIf_;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use Psr\Container\ContainerInterface;
use Symfony\Component\JsonEncoder\DataModel\Encode\CollectionNode;
use Symfony\Component\JsonEncoder\DataModel\Encode\CompositeNode;
use Symfony\Component\JsonEncoder\DataModel\Encode\DataModelNodeInterface;
use Symfony\Component\JsonEncoder\DataModel\Encode\ObjectNode;
use Symfony\Component\JsonEncoder\DataModel\Encode\ScalarNode;
use Symfony\Component\JsonEncoder\Exception\LogicException;
use Symfony\Component\JsonEncoder\Exception\RuntimeException;
use Symfony\Component\JsonEncoder\Exception\UnexpectedValueException;
use Symfony\Component\JsonEncoder\PhpAstBuilderTrait;
use Symfony\Component\JsonEncoder\Stream\StreamWriterInterface;
use Symfony\Component\TypeInfo\Exception\LogicException as TypeInfoLogicException;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * Builds a PHP syntax tree that encodes data to JSON.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class PhpAstBuilder
{
    use PhpAstBuilderTrait;

    public function __construct()
    {
        $this->builder = new BuilderFactory();
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $context
     *
     * @return list<Stmt>
     */
    public function build(DataModelNodeInterface $dataModel, EncodeAs $encodeAs, array $config, array $context = []): array
    {
        $closureStmts = $this->buildClosureStatements($dataModel, $encodeAs, $config, $context);

        return match ($encodeAs) {
            EncodeAs::STRING => [new Return_(new Closure([
                'static' => true,
                'params' => [
                    new Param($this->builder->var('data'), type: 'mixed'),
                    new Param($this->builder->var('config'), type: 'array'),
                    new Param($this->builder->var('services'), type: new NullableType(new FullyQualified(ContainerInterface::class))),
                ],
                'returnType' => new FullyQualified(\Traversable::class),
                'stmts' => $closureStmts,
            ]))],

            EncodeAs::STREAM => [new Return_(new Closure([
                'static' => true,
                'params' => [
                    new Param($this->builder->var('data'), type: 'mixed'),
                    new Param($this->builder->var('stream'), type: new FullyQualified(StreamWriterInterface::class)),
                    new Param($this->builder->var('config'), type: 'array'),
                    new Param($this->builder->var('services'), type: new NullableType(new FullyQualified(ContainerInterface::class))),
                ],
                'returnType' => 'void',
                'stmts' => $closureStmts,
            ]))],

            EncodeAs::RESOURCE => [new Return_(new Closure([
                'static' => true,
                'params' => [
                    new Param($this->builder->var('data'), type: 'mixed'),
                    new Param($this->builder->var('stream'), type: 'mixed'),
                    new Param($this->builder->var('config'), type: 'array'),
                    new Param($this->builder->var('services'), type: new NullableType(new FullyQualified(ContainerInterface::class))),
                ],
                'returnType' => 'void',
                'stmts' => $closureStmts,
            ]))],
        };
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $context
     *
     * @return list<Stmt>
     */
    public function buildClosureStatements(DataModelNodeInterface $dataModelNode, EncodeAs $encodeAs, array $config, array $context): array
    {
        // TODO handle union
        $setupStmts = [];
        $accessor = $this->convertDataAccessorToPhpExpr($dataModelNode->getAccessor());

        if (true === ($context['root'] ?? true)) {
            $context['root'] = false;
            $setupStmts = [
                new Expression(new Assign(
                    $this->builder->var('flags'),
                    new Coalesce(new ArrayDimFetch($this->builder->var('config'), $this->builder->val('json_encode_flags')), $this->builder->val(0)),
                )),
            ];
        }

        if ($dataModelNode instanceof ScalarNode) {
            $scalarAccessor = match (true) {
                $dataModelNode->getType() instanceof BackedEnumType => $this->encodeValue(new PropertyFetch($accessor, 'value')),
                TypeIdentifier::NULL === $dataModelNode->getType()->getBaseType()->getTypeIdentifier() => $this->builder->val('null'),
                default => $this->encodeValue($accessor),
            };

            return [
                ...$setupStmts,
                $this->yieldJson($scalarAccessor, $encodeAs)
            ];
        }

        if (!$this->isNodeAlteringJson($dataModelNode)) {
            return [
                ...$setupStmts,
                $this->yieldJson($this->encodeValue($accessor), $encodeAs),
            ];
        }

        if ($dataModelNode instanceof CompositeNode) {
            $stmtsAndConditions = array_map(fn (DataModelNodeInterface $n): array => [
                'condition' =>$this->getNodeCondition($n),
                'stmts' => $this->buildClosureStatements($n, $encodeAs, $config, $context),
            ], $dataModelNode->nodes);

            $if = $stmtsAndConditions[0];
            unset($stmtsAndConditions[0]);

            return [
                ...$setupStmts,
                new If_($if['condition'], [
                    'stmts' => $if['stmts'],
                    'elseifs' => array_map(fn (array $s): ElseIf_ => new ElseIf_($s['condition'], $s['stmts']), $stmtsAndConditions),
                    'else' => new Else_([
                        new Expression(new Throw_(new New_(new FullyQualified(UnexpectedValueException::class), [$this->builder->funcCall('\sprintf', [
                            $this->builder->val('Unexpected "%s" value.'),
                            $this->builder->funcCall('\get_debug_type', [$accessor]),
                        ])]))),
                    ]),
                ]),
            ];
        }

        if ($dataModelNode instanceof CollectionNode) {
            $prefixName = $this->scopeVariableName('prefix', $context);

            if ($dataModelNode->getType()->isList()) {
                return [
                    ...$setupStmts,
                    $this->yieldJson($this->builder->val('['), $encodeAs),
                    new Expression(new Assign($this->builder->var($prefixName), $this->builder->val(''))),
                    new Foreach_($accessor, $this->convertDataAccessorToPhpExpr($dataModelNode->item->accessor), [
                        'stmts' => [
                            $this->yieldJson($this->builder->var($prefixName), $encodeAs),
                            ...$this->buildClosureStatements($dataModelNode->item, $encodeAs, $config, $context),
                            new Expression(new Assign($this->builder->var($prefixName), $this->builder->val(','))),
                        ],
                    ]),
                    $this->yieldJson($this->builder->val(']'), $encodeAs),
                ];
            }

            $keyName = $this->scopeVariableName('key', $context);

            return [
                ...$setupStmts,
                $this->yieldJson($this->builder->val('{'), $encodeAs),
                new Expression(new Assign($this->builder->var($prefixName), $this->builder->val(''))),
                new Foreach_($accessor, $this->convertDataAccessorToPhpExpr($dataModelNode->item->accessor), [
                    'keyVar' => $this->builder->var($keyName),
                    'stmts' => [
                        new Expression(new Assign($this->builder->var($keyName), $this->escapeString($this->builder->var($keyName)))),
                        $this->yieldJson(new Encapsed([
                            $this->builder->var($prefixName),
                            new EncapsedStringPart('"'),
                            $this->builder->var($keyName),
                            new EncapsedStringPart('":'),
                        ]), $encodeAs),
                        ...$this->buildClosureStatements($dataModelNode->item, $encodeAs, $config, $context),
                        new Expression(new Assign($this->builder->var($prefixName), $this->builder->val(','))),
                    ],
                ]),
                $this->yieldJson($this->builder->val('}'), $encodeAs),
            ];
        }

        if ($dataModelNode instanceof ObjectNode) {
            $objectStmts = [$this->yieldJson($this->builder->val('{'), $encodeAs)];
            $separator = '';

            foreach ($dataModelNode->properties as $name => $propertyNode) {
                $encodedName = json_encode($name);
                if (false === $encodedName) {
                    throw new RuntimeException(sprintf('Cannot encode "%s"', $name));
                }

                $encodedName = substr($encodedName, 1, -1);

                $objectStmts = [
                    ...$objectStmts,
                    $this->yieldJson($this->builder->val($separator), $encodeAs),
                    $this->yieldJson($this->builder->val('"'), $encodeAs),
                    $this->yieldJson($this->builder->val($encodedName), $encodeAs),
                    $this->yieldJson($this->builder->val('":'), $encodeAs),
                    ...$this->buildClosureStatements($propertyNode, $encodeAs, $config, $context),
                ];

                $separator = ',';
            }

            $objectStmts[] = $this->yieldJson($this->builder->val('}'), $encodeAs);

            return [...$setupStmts, ...$objectStmts];
        }

        throw new LogicException(sprintf('Unexpected "%s" node', $dataModelNode::class));
    }

    private function encodeValue(Expr $value): Expr
    {
        return $this->builder->funcCall('\json_encode', [$value, $this->builder->var('flags')]);
    }

    private function escapeString(Expr $string): Expr
    {
        return $this->builder->funcCall('\substr', [$this->encodeValue($string), $this->builder->val(1), $this->builder->val(-1)]);
    }

    private function yieldJson(Expr $json, EncodeAs $encodeAs): Stmt
    {
        return new Expression(match ($encodeAs) {
            EncodeAs::STRING => new Yield_($json),
            EncodeAs::STREAM => $this->builder->methodCall($this->builder->var('stream'), 'write', [$json]),
            EncodeAs::RESOURCE => $this->builder->funcCall('\fwrite', [$this->builder->var('stream'), $json]),
        });
    }

    private function getNodeCondition(DataModelNodeInterface $node): Expr
    {
        $accessor = $this->convertDataAccessorToPhpExpr($node->getAccessor());
        $type = $node->getType()->getBaseType();

        return match (true) {
            $type instanceof ObjectType => new Instanceof_($accessor, new FullyQualified($type->getClassName())),
            TypeIdentifier::NULL === $type->getTypeIdentifier() => new Identical($this->builder->val(null), $accessor),
            TypeIdentifier::MIXED === $type->getTypeIdentifier() => $this->builder->val(true),
            default => $this->builder->funcCall('\is_'.$type->getTypeIdentifier()->value, [$accessor]),
        };
    }
}
