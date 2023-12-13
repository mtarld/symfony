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
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\Encapsed;
use PhpParser\Node\Scalar\EncapsedStringPart;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use Psr\Container\ContainerInterface;
use Symfony\Component\JsonEncoder\DataModel\Encode\CollectionNode;
use Symfony\Component\JsonEncoder\DataModel\Encode\DataModelNodeInterface;
use Symfony\Component\JsonEncoder\DataModel\Encode\ObjectNode;
use Symfony\Component\JsonEncoder\DataModel\Encode\ScalarNode;
use Symfony\Component\JsonEncoder\Exception\LogicException;
use Symfony\Component\JsonEncoder\Exception\RuntimeException;
use Symfony\Component\JsonEncoder\PhpAstBuilderTrait;
use Symfony\Component\JsonEncoder\Stream\StreamWriterInterface;
use Symfony\Component\TypeInfo\Type;

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

        if (!$this->isNodeAlteringJson($dataModelNode)) {
            return [
                ...$setupStmts,
                $this->yieldJson($this->encodeValue($accessor), $encodeAs),
            ];
        }

        if ($dataModelNode instanceof CollectionNode) {
            $prefixName = $this->scopeVariableName('prefix', $context);

            if ($dataModelNode->getType()->isList()) {
                $listStmts = [
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

                if ($dataModelNode->getType()->isNullable()) {
                    return [
                        ...$setupStmts,
                        new If_(new Identical($this->builder->val(null), $accessor), [
                            'stmts' => [$this->yieldJson($this->builder->val('null'), $encodeAs)],
                            'else' => new Else_($listStmts),
                        ]),
                    ];
                }

                return [...$setupStmts, ...$listStmts];
            }

            $keyName = $this->scopeVariableName('key', $context);

            $dictStmts = [
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

            if ($dataModelNode->getType()->isNullable()) {
                return [
                    ...$setupStmts,
                    new If_(new Identical($this->builder->val(null), $accessor), [
                        'stmts' => [$this->yieldJson($this->builder->val('null'), $encodeAs)],
                        'else' => new Else_($dictStmts),
                    ]),
                ];
            }

            return [...$setupStmts, ...$dictStmts];
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

            if ($dataModelNode->getType()->isNullable()) {
                return [
                    ...$setupStmts,
                    new If_(new Identical($this->builder->val(null), $accessor), [
                        'stmts' => [$this->yieldJson($this->builder->val('null'), $encodeAs)],
                        'else' => new Else_($objectStmts),
                    ]),
                ];
            }

            return [...$setupStmts, ...$objectStmts];
        }

        if ($dataModelNode instanceof ScalarNode) {
            $scalarAccessor = $accessor;

            $type = $dataModelNode->getType();
            if ($type->isBackedEnum()) {
                $scalarAccessor = $type->isNullable() ? new NullsafePropertyFetch($accessor, 'value') : new PropertyFetch($accessor, 'value');
            }

            $scalarStmts = [$this->yieldJson($this->encodeValue($scalarAccessor), $encodeAs)];

            if ($type->isNullable() && !$type->isBackedEnum() && !\in_array($type->getBuiltinType(), [Type::BUILTIN_TYPE_MIXED, Type::BUILTIN_TYPE_NULL], true)) {
                return [
                    ...$setupStmts,
                    new If_(new Identical($this->builder->val(null), $accessor), [
                        'stmts' => [$this->yieldJson($this->builder->val('null'), $encodeAs)],
                        'else' => new Else_($scalarStmts),
                    ]),
                ];
            }

            return [...$setupStmts, ...$scalarStmts];
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
}
