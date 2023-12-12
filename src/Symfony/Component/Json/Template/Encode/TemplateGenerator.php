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

use PhpParser\BuilderFactory;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Scalar\Encapsed;
use PhpParser\Node\Scalar\EncapsedStringPart;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use Symfony\Component\Encoder\DataModel\Encode\CollectionNode;
use Symfony\Component\Encoder\DataModel\Encode\DataModelNodeInterface;
use Symfony\Component\Encoder\DataModel\Encode\ObjectNode;
use Symfony\Component\Encoder\DataModel\Encode\ScalarNode;
use Symfony\Component\Encoder\Exception\LogicException;
use Symfony\Component\Encoder\Exception\RuntimeException;
use Symfony\Component\Encoder\VariableNameScoperTrait;
use Symfony\Component\Json\Template\TemplateGeneratorTrait;
use Symfony\Component\TypeInfo\Type;

/**
 * Generates a template PHP syntax tree that encodes data to JSON.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class TemplateGenerator
{
    use TemplateGeneratorTrait;
    use VariableNameScoperTrait;

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
    public function generate(DataModelNodeInterface $node, array $config, array $context): array
    {
        $setupStmts = [];
        $accessor = $this->convertDataAccessorToPhpExpr($node->getAccessor());

        if (true === ($context['root'] ?? true)) {
            $context['root'] = false;
            $setupStmts = [
                new Expression(new Assign(
                    $this->builder->var('flags'),
                    new Coalesce(new ArrayDimFetch($this->builder->var('config'), $this->builder->val('json_encode_flags')), $this->builder->val(0)),
                )),
            ];
        }

        if (!$this->isNodeAlteringJson($node)) {
            return [
                ...$setupStmts,
                $this->yieldJson($this->encodeValue($accessor), $context),
            ];
        }

        if ($node instanceof CollectionNode) {
            $prefixName = $this->scopeVariableName('prefix', $context);

            if ($node->getType()->isList()) {
                $listStmts = [
                    $this->yieldJson($this->builder->val('['), $context),
                    new Expression(new Assign($this->builder->var($prefixName), $this->builder->val(''))),
                    new Foreach_($accessor, $this->convertDataAccessorToPhpExpr($node->item->accessor), [
                        'stmts' => [
                            $this->yieldJson($this->builder->var($prefixName), $context),
                            ...$this->generate($node->item, $config, $context),
                            new Expression(new Assign($this->builder->var($prefixName), $this->builder->val(','))),
                        ],
                    ]),
                    $this->yieldJson($this->builder->val(']'), $context),
                ];

                if ($node->getType()->isNullable()) {
                    return [
                        ...$setupStmts,
                        new If_(new Identical($this->builder->val(null), $accessor), [
                            'stmts' => [$this->yieldJson($this->builder->val('null'), $context)],
                            'else' => new Else_($listStmts),
                        ]),
                    ];
                }

                return [...$setupStmts, ...$listStmts];
            }

            $keyName = $this->scopeVariableName('key', $context);

            $dictStmts = [
                $this->yieldJson($this->builder->val('{'), $context),
                new Expression(new Assign($this->builder->var($prefixName), $this->builder->val(''))),
                new Foreach_($accessor, $this->convertDataAccessorToPhpExpr($node->item->accessor), [
                    'keyVar' => $this->builder->var($keyName),
                    'stmts' => [
                        new Expression(new Assign($this->builder->var($keyName), $this->escapeString($this->builder->var($keyName)))),
                        $this->yieldJson(new Encapsed([
                            $this->builder->var($prefixName),
                            new EncapsedStringPart('"'),
                            $this->builder->var($keyName),
                            new EncapsedStringPart('":'),
                        ]), $context),
                        ...$this->generate($node->item, $config, $context),
                        new Expression(new Assign($this->builder->var($prefixName), $this->builder->val(','))),
                    ],
                ]),
                $this->yieldJson($this->builder->val('}'), $context),
            ];

            if ($node->getType()->isNullable()) {
                return [
                    ...$setupStmts,
                    new If_(new Identical($this->builder->val(null), $accessor), [
                        'stmts' => [$this->yieldJson($this->builder->val('null'), $context)],
                        'else' => new Else_($dictStmts),
                    ]),
                ];
            }

            return [...$setupStmts, ...$dictStmts];
        }

        if ($node instanceof ObjectNode) {
            $objectStmts = [$this->yieldJson($this->builder->val('{'), $context)];
            $separator = '';

            foreach ($node->properties as $name => $propertyNode) {
                $encodedName = json_encode($name);
                if (false === $encodedName) {
                    throw new RuntimeException(sprintf('Cannot encode "%s"', $name));
                }

                $encodedName = substr($encodedName, 1, -1);

                $objectStmts = [
                    ...$objectStmts,
                    $this->yieldJson($this->builder->val($separator), $context),
                    $this->yieldJson($this->builder->val('"'), $context),
                    $this->yieldJson($this->builder->val($encodedName), $context),
                    $this->yieldJson($this->builder->val('":'), $context),
                    ...$this->generate($propertyNode, $config, $context),
                ];

                $separator = ',';
            }

            $objectStmts[] = $this->yieldJson($this->builder->val('}'), $context);

            if ($node->getType()->isNullable()) {
                return [
                    ...$setupStmts,
                    new If_(new Identical($this->builder->val(null), $accessor), [
                        'stmts' => [$this->yieldJson($this->builder->val('null'), $context)],
                        'else' => new Else_($objectStmts),
                    ]),
                ];
            }

            return [...$setupStmts, ...$objectStmts];
        }

        if ($node instanceof ScalarNode) {
            $scalarAccessor = $accessor;

            $type = $node->getType();
            if ($type->isBackedEnum()) {
                $scalarAccessor = $type->isNullable() ? new NullsafePropertyFetch($accessor, 'value') : new PropertyFetch($accessor, 'value');
            }

            $scalarStmts = [$this->yieldJson($this->encodeValue($scalarAccessor), $context)];

            if ($type->isNullable() && !$type->isBackedEnum() && !\in_array($type->getBuiltinType(), [Type::BUILTIN_TYPE_MIXED, Type::BUILTIN_TYPE_NULL], true)) {
                return [
                    ...$setupStmts,
                    new If_(new Identical($this->builder->val(null), $accessor), [
                        'stmts' => [$this->yieldJson($this->builder->val('null'), $context)],
                        'else' => new Else_($scalarStmts),
                    ]),
                ];
            }

            return [...$setupStmts, ...$scalarStmts];
        }

        throw new LogicException(sprintf('Unexpected "%s" node', $node::class));
    }

    private function encodeValue(Expr $value): Expr
    {
        return $this->builder->funcCall('\json_encode', [$value, $this->builder->var('flags')]);
    }

    private function escapeString(Expr $string): Expr
    {
        return $this->builder->funcCall('\substr', [$this->encodeValue($string), $this->builder->val(1), $this->builder->val(-1)]);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function yieldJson(Expr $json, array $context): Stmt
    {
        $expr = match ($context['stream_type']) {
            'resource' => $this->builder->funcCall('\fwrite', [$this->builder->var('stream'), $json]),
            'stream' => $this->builder->methodCall($this->builder->var('stream'), 'write', [$json]),
            default => new Yield_($json),
        };

        return new Expression($expr);
    }
}
