<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Decode;

// TODO remove json_encode/decode flags

use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\Cast\Object_ as ObjectCast;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ClosureUse;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\MatchArm;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use Psr\Container\ContainerInterface;
use Symfony\Component\JsonEncoder\DataModel\Decode\CollectionNode;
use Symfony\Component\JsonEncoder\DataModel\Decode\CompositeNode;
use Symfony\Component\JsonEncoder\DataModel\Decode\DataModelNodeInterface;
use Symfony\Component\JsonEncoder\DataModel\Decode\ObjectNode;
use Symfony\Component\JsonEncoder\DataModel\Decode\ScalarNode;
use Symfony\Component\JsonEncoder\DataModel\PhpExprDataAccessor;
use Symfony\Component\JsonEncoder\Exception\LogicException;
use Symfony\Component\JsonEncoder\Exception\UnexpectedValueException;
use Symfony\Component\JsonEncoder\Instantiator\InstantiatorInterface;
use Symfony\Component\JsonEncoder\Instantiator\LazyInstantiatorInterface;
use Symfony\Component\JsonEncoder\PhpAstBuilderTrait;
use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * Builds a PHP syntax tree that decodes JSON.
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
    public function build(DataModelNodeInterface $dataModel, DecodeFrom $decodeFrom, array $config, array $context = []): array
    {
        return match ($decodeFrom) {
            DecodeFrom::STRING => [new Return_(new Closure([
                'static' => true,
                'params' => [
                    new Param($this->builder->var('string'), type: 'string'),
                    new Param($this->builder->var('config'), type: 'array'),
                    new Param($this->builder->var('instantiator'), type: new FullyQualified(InstantiatorInterface::class)),
                    new Param($this->builder->var('services'), type: new NullableType(new FullyQualified(ContainerInterface::class))),
                ],
                'returnType' => 'mixed',
                'stmts' => [
                    new Expression(new Assign(
                        $this->builder->var('flags'),
                        new Coalesce(new ArrayDimFetch($this->builder->var('config'), $this->builder->val('json_decode_flags')), $this->builder->val(0)),
                    )),
                    ...$this->buildProvidersStatements($dataModel, $decodeFrom, $context),
                    new Return_(
                        $this->isNodeAlteringJson($dataModel, $decodeFrom)
                        ? $this->builder->funcCall(new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($dataModel->getIdentifier())), [
                            $this->builder->staticCall(new FullyQualified(NativeDecoder::class), 'decodeString', [$this->builder->var('string'), $this->builder->var('flags')]),
                        ])
                        : $this->builder->staticCall(new FullyQualified(NativeDecoder::class), 'decodeString', [$this->builder->var('string'), $this->builder->var('flags')]),
                    ),
                ],
            ]))],

            DecodeFrom::STREAM, DecodeFrom::RESOURCE => [new Return_(new Closure([
                'static' => true,
                'params' => [
                    new Param($this->builder->var('stream'), type: 'mixed'),
                    new Param($this->builder->var('config'), type: 'array'),
                    new Param($this->builder->var('instantiator'), type: new FullyQualified(LazyInstantiatorInterface::class)),
                    new Param($this->builder->var('services'), type: new NullableType(new FullyQualified(ContainerInterface::class))),
                ],
                'returnType' => 'mixed',
                'stmts' => [
                    new Expression(new Assign(
                        $this->builder->var('flags'),
                        new Coalesce(new ArrayDimFetch($this->builder->var('config'), $this->builder->val('json_decode_flags')), $this->builder->val(0)),
                    )),
                    ...$this->buildProvidersStatements($dataModel, $decodeFrom, $context),
                    new Return_(
                        $this->isNodeAlteringJson($dataModel, $decodeFrom)
                        ? $this->builder->funcCall(new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($dataModel->getIdentifier())), [
                            $this->builder->var('stream'),
                            $this->builder->val(0),
                            $this->builder->val(null),
                        ])
                        : $this->builder->staticCall(new FullyQualified(NativeDecoder::class), 'decodeStream', [
                            $this->builder->var('stream'),
                            $this->builder->val(0),
                            $this->builder->val(null),
                        ]),
                    ),
                ],
            ]))],
        };
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<Stmt>
     */
    private function buildProvidersStatements(DataModelNodeInterface $node, DecodeFrom $decodeFrom, array &$context): array
    {
        if ($context['providers'][$node->getIdentifier()] ?? false) {
            return [];
        }

        $context['providers'][$node->getIdentifier()] = true;

        if (!$this->isNodeAlteringJson($node, $decodeFrom)) {
            return [];
        }

        return match (true) {
            $node instanceof ScalarNode => $this->buildScalarProviderStatements($node, $decodeFrom),
            $node instanceof CompositeNode => $this->buildCompositeNodeStatements($node, $decodeFrom, $context),
            $node instanceof CollectionNode => $this->buildCollectionNodeStatements($node, $decodeFrom, $context),
            $node instanceof ObjectNode => $this->buildObjectNodeStatements($node, $decodeFrom, $context),
            default => throw new LogicException(sprintf('Unexpected "%s" data model node', $node::class)),
        };
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<Stmt>
     */
    private function buildScalarProviderStatements(ScalarNode $node, DecodeFrom $decodeFrom): array
    {
        $accessor = DecodeFrom::STRING === $decodeFrom
            ? $this->builder->var('data')
            : $this->builder->staticCall(new FullyQualified(NativeDecoder::class), 'decodeStream', [
                $this->builder->var('stream'),
                $this->builder->var('offset'),
                $this->builder->var('length'),
            ]);

        $params = DecodeFrom::STRING === $decodeFrom
            ? [new Param($this->builder->var('data'))]
            : [new Param($this->builder->var('stream')), new Param($this->builder->var('offset')), new Param($this->builder->var('length'))];

        return [
            new Expression(new Assign(
                new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($node->getIdentifier())),
                new Closure([
                    'static' => true,
                    'params' => $params,
                    'uses' => [new ClosureUse($this->builder->var('flags'))],
                    'stmts' => [new Return_($this->buildFormatScalarStatement($node, $accessor))],
                ]),
            )),
        ];
    }

    private function buildFormatScalarStatement(ScalarNode $node, Expr $accessor): Node
    {
        $type = $node->getType();

        return match (true) {
            $type instanceof BackedEnumType => $this->builder->staticCall(new FullyQualified($type->getClassName()), 'from', [$accessor]),
            TypeIdentifier::NULL === $type->getTypeIdentifier() => $this->builder->val(null),
            TypeIdentifier::OBJECT === $type->getTypeIdentifier() => new ObjectCast($accessor),
            default => $accessor,
        };
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<Stmt>
     */
    private function buildCompositeNodeStatements(CompositeNode $node, DecodeFrom $decodeFrom, array &$context): array
    {
        $prepareDataStmts = DecodeFrom::STRING === $decodeFrom ? [] : [
            new Expression(new Assign($this->builder->var('data'), $this->builder->staticCall(new FullyQualified(NativeDecoder::class), 'decodeStream', [
                $this->builder->var('stream'),
                $this->builder->var('offset'),
                $this->builder->var('length'),
            ]))),
        ];

        $providersStmts = [];
        $nodesStmts = [];

        $nodeCondition = function (DataModelNodeInterface $node, Expr $accessor): Expr {
            $type = $node->getType()->getBaseType();

            // TODO remove support of EnumType
            if ($type instanceof BackedEnumType) {
                return $this->builder->funcCall('\is_'.$type->getBackingType()->getTypeIdentifier()->value, [$this->builder->var('data')]);
            }

            if ($type instanceof ObjectType) {
                return $this->builder->funcCall('\is_array', [$this->builder->var('data')]);
            }

            if ($node instanceof CollectionNode) {
                return $node->getType()->isList()
                    ? new BooleanAnd($this->builder->funcCall('\is_array', [$this->builder->var('data')]), $this->builder->funcCall('\array_is_list', [$this->builder->var('data')]))
                    : $this->builder->funcCall('\is_array', [$this->builder->var('data')]);
            }

            if (TypeIdentifier::NULL === $type->getTypeIdentifier()) {
                return new Identical($this->builder->val(null), $this->builder->var('data'));
            }

            if (TypeIdentifier::MIXED === $type->getTypeIdentifier()) {
                return $this->builder->val(true);
            }

            return $this->builder->funcCall('\is_'.$type->getTypeIdentifier()->value, [$this->builder->var('data')]);
        };

        foreach ($node->nodes as $n) {
            if ($this->isNodeAlteringJson($n, $decodeFrom)) {
                $providersStmts = [...$providersStmts, ...$this->buildProvidersStatements($n, $decodeFrom, $context)];
                $nodeValueStmt = $this->builder->funcCall(
                    new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($n->getIdentifier())),
                    [$this->builder->var('data')],
                );
            } else {
                $nodeValueStmt = $this->buildFormatScalarStatement($n, $this->builder->var('data'));
            }

            $nodesStmts[] = new If_($nodeCondition($n, $this->builder->var('data')), ['stmts' => [new Return_($nodeValueStmt)]]);
        }

        $params = DecodeFrom::STRING === $decodeFrom
            ? [new Param($this->builder->var('data'))]
            : [new Param($this->builder->var('stream')), new Param($this->builder->var('offset')), new Param($this->builder->var('length'))];

        return [
            ...$providersStmts,
            new Expression(new Assign(
                new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($node->getIdentifier())),
                new Closure([
                    'static' => true,
                    'params' => $params,
                    'uses' => [
                        new ClosureUse($this->builder->var('config')),
                        new ClosureUse($this->builder->var('instantiator')),
                        new ClosureUse($this->builder->var('services')),
                        new ClosureUse($this->builder->var('providers'), byRef: true),
                        new ClosureUse($this->builder->var('flags')),
                    ],
                    'stmts' => [
                        ...$prepareDataStmts,
                        ...$nodesStmts,
                        new Expression(new Throw_(new New_(new FullyQualified(UnexpectedValueException::class), [$this->builder->funcCall('\sprintf', [
                            $this->builder->val(sprintf('Unexpected "%%s" value for "%s".', $node->getIdentifier())),
                            $this->builder->funcCall('\get_debug_type', [$this->builder->var('data')]),
                        ])]))),
                    ],
                ]),
            )),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<Stmt>
     */
    private function buildCollectionNodeStatements(CollectionNode $node, DecodeFrom $decodeFrom, array &$context): array
    {
        if (DecodeFrom::STRING === $decodeFrom) {
            $itemValueStmt = $this->isNodeAlteringJson($node->item, $decodeFrom)
                ? $this->builder->funcCall(
                    new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($node->item->getIdentifier())),
                    [$this->builder->var('v')],
                )
                : $this->builder->var('v');
        } else {
            $itemValueStmt = $this->isNodeAlteringJson($node->item, $decodeFrom)
                ? $this->builder->funcCall(
                    new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($node->item->getIdentifier())), [
                        $this->builder->var('stream'),
                        new ArrayDimFetch($this->builder->var('v'), $this->builder->val(0)),
                        new ArrayDimFetch($this->builder->var('v'), $this->builder->val(1)),
                    ],
                ) : $this->buildFormatScalarStatement(
                    $node->item,
                    $this->builder->staticCall(new FullyQualified(NativeDecoder::class), 'decodeStream', [
                        $this->builder->var('stream'),
                        new ArrayDimFetch($this->builder->var('v'), $this->builder->val(0)),
                        new ArrayDimFetch($this->builder->var('v'), $this->builder->val(1)),
                    ]),
                );
        }

        $iterableClosureParams = DecodeFrom::STRING === $decodeFrom
            ? [new Param($this->builder->var('data'))]
            : [new Param($this->builder->var('stream')), new Param($this->builder->var('data'))];

        $iterableClosureStmts = [
            new Expression(new Assign(
                $this->builder->var('iterable'),
                new Closure([
                    'static' => true,
                    'params' => $iterableClosureParams,
                    'uses' => [
                        new ClosureUse($this->builder->var('config')),
                        new ClosureUse($this->builder->var('instantiator')),
                        new ClosureUse($this->builder->var('services')),
                        new ClosureUse($this->builder->var('providers'), byRef: true),
                        new ClosureUse($this->builder->var('flags')),
                    ],
                    'stmts' => [
                        new Foreach_($this->builder->var('data'), $this->builder->var('v'), [
                            'keyVar' => $this->builder->var('k'),
                            'stmts' => [new Expression(new Yield_($itemValueStmt, $this->builder->var('k')))],
                        ]),
                    ],
                ]),
            )),
        ];

        $iterableValueStmt = DecodeFrom::STRING === $decodeFrom
            ? $this->builder->funcCall($this->builder->var('iterable'), [$this->builder->var('data')])
            : $this->builder->funcCall($this->builder->var('iterable'), [$this->builder->var('stream'), $this->builder->var('data')]);

        $prepareDataStmts = DecodeFrom::STRING === $decodeFrom ? [] : [
            new Expression(new Assign($this->builder->var('data'), $this->builder->staticCall(
                new FullyQualified(Splitter::class),
                $node->getType()->isList() ? 'splitList' : 'splitDict',
                [$this->builder->var('stream'), $this->builder->var('offset'), $this->builder->var('length')],
            ))),
        ];

        $params = DecodeFrom::STRING === $decodeFrom
            ? [new Param($this->builder->var('data'))]
            : [new Param($this->builder->var('stream')), new Param($this->builder->var('offset')), new Param($this->builder->var('length'))];

        return [
            new Expression(new Assign(
                new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($node->getIdentifier())),
                new Closure([
                    'static' => true,
                    'params' => $params,
                    'uses' => [
                        new ClosureUse($this->builder->var('config')),
                        new ClosureUse($this->builder->var('instantiator')),
                        new ClosureUse($this->builder->var('services')),
                        new ClosureUse($this->builder->var('providers'), byRef: true),
                        new ClosureUse($this->builder->var('flags')),
                    ],
                    'stmts' => [
                        ...$prepareDataStmts,
                        ...$iterableClosureStmts,
                        new Return_($node->getType()->isA(TypeIdentifier::ARRAY) ? $this->builder->funcCall('\iterator_to_array', [$iterableValueStmt]) : $iterableValueStmt),
                    ],
                ]),
            )),
            ...($this->isNodeAlteringJson($node->item, $decodeFrom) ? $this->buildProvidersStatements($node->item, $decodeFrom, $context) : []),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<Stmt>
     */
    private function buildObjectNodeStatements(ObjectNode $node, DecodeFrom $decodeFrom, array &$context): array
    {
        if ($node->ghost) {
            return [];
        }

        $propertyValueProvidersStmts = [];
        $propertiesValuesStmts = [];

        foreach ($node->properties as $encodedName => $property) {
            $propertyValueProvidersStmts = [
                ...$propertyValueProvidersStmts,
                ...($this->isNodeAlteringJson($property['value'], $decodeFrom) ? $this->buildProvidersStatements($property['value'], $decodeFrom, $context) : []),
            ];

            if (DecodeFrom::STRING === $decodeFrom) {
                $propertyValueStmt = $this->isNodeAlteringJson($property['value'], $decodeFrom)
                    ? new Ternary(
                        $this->builder->funcCall('\array_key_exists', [$this->builder->val($encodedName), $this->builder->var('data')]),
                        $this->builder->funcCall(
                            new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($property['value']->getIdentifier())),
                            [new ArrayDimFetch($this->builder->var('data'), $this->builder->val($encodedName))],
                        ),
                        $this->builder->val('_symfony_missing_value'),
                    )
                    : new Coalesce(new ArrayDimFetch($this->builder->var('data'), $this->builder->val($encodedName)), $this->builder->val('_symfony_missing_value'));

                $propertiesValuesStmts[] = new ArrayItem(
                    $this->convertDataAccessorToPhpExpr($property['accessor'](new PhpExprDataAccessor($propertyValueStmt))),
                    $this->builder->val($property['name']),
                );
            } else {
                $propertyValueStmt = $this->isNodeAlteringJson($property['value'], $decodeFrom)
                    ? $this->builder->funcCall(
                        new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($property['value']->getIdentifier())), [
                            $this->builder->var('stream'),
                            new ArrayDimFetch($this->builder->var('v'), $this->builder->val(0)),
                            new ArrayDimFetch($this->builder->var('v'), $this->builder->val(1)),
                        ],
                    )
                    : $this->buildFormatScalarStatement(
                        $property['value'],
                        $this->builder->staticCall(new FullyQualified(NativeDecoder::class), 'decodeStream', [
                            $this->builder->var('stream'),
                            new ArrayDimFetch($this->builder->var('v'), $this->builder->val(0)),
                            new ArrayDimFetch($this->builder->var('v'), $this->builder->val(1)),
                        ]),
                    );

                $propertiesValuesStmts[] = new MatchArm([$this->builder->val($encodedName)], new Assign(
                    new ArrayDimFetch($this->builder->var('properties'), $this->builder->val($property['name'])),
                    new Closure([
                        'static' => true,
                        'uses' => [
                            new ClosureUse($this->builder->var('stream')),
                            new ClosureUse($this->builder->var('v')),
                            new ClosureUse($this->builder->var('config')),
                            new ClosureUse($this->builder->var('instantiator')),
                            new ClosureUse($this->builder->var('services')),
                            new ClosureUse($this->builder->var('providers'), byRef: true),
                            new ClosureUse($this->builder->var('flags')),
                        ],
                        'stmts' => [
                            new Return_($this->convertDataAccessorToPhpExpr($property['accessor'](new PhpExprDataAccessor($propertyValueStmt)))),
                        ],
                    ]),
                ));
            }
        }

        $params = DecodeFrom::STRING === $decodeFrom
            ? [new Param($this->builder->var('data'))]
            : [new Param($this->builder->var('stream')), new Param($this->builder->var('offset')), new Param($this->builder->var('length'))];

        $prepareDataStmts = DecodeFrom::STRING === $decodeFrom ? [] : [
            new Expression(new Assign($this->builder->var('data'), $this->builder->staticCall(
                new FullyQualified(Splitter::class),
                'splitDict',
                [$this->builder->var('stream'), $this->builder->var('offset'), $this->builder->var('length')],
            ))),
        ];

        if (DecodeFrom::STRING === $decodeFrom) {
            $instantiateStmts = [
                new Return_($this->builder->methodCall($this->builder->var('instantiator'), 'instantiate', [
                    new ClassConstFetch(new FullyQualified($node->getType()->getClassName()), 'class'),
                    $this->builder->funcCall('\array_filter', [
                        new Array_($propertiesValuesStmts, ['kind' => Array_::KIND_SHORT]),
                        new Closure([
                            'static' => true,
                            'params' => [new Param($this->builder->var('v'))],
                            'stmts' => [new Return_(new NotIdentical($this->builder->val('_symfony_missing_value'), $this->builder->var('v')))],
                        ]),
                    ]),
                ])),
            ];
        } else {
            $instantiateStmts = [
                new Expression(new Assign($this->builder->var('properties'), new Array_([], ['kind' => Array_::KIND_SHORT]))),
                new Foreach_($this->builder->var('data'), $this->builder->var('v'), [
                    'keyVar' => $this->builder->var('k'),
                    'stmts' => [new Expression(new Match_(
                        $this->builder->var('k'),
                        [...$propertiesValuesStmts, new MatchArm(null, $this->builder->val(null))],
                    ))],
                ]),
                new Return_($this->builder->methodCall($this->builder->var('instantiator'), 'instantiate', [
                    new ClassConstFetch(new FullyQualified($node->getType()->getClassName()), 'class'),
                    $this->builder->var('properties'),
                ])),
            ];
        }

        return [
            new Expression(new Assign(
                new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($node->getIdentifier())),
                new Closure([
                    'static' => true,
                    'params' => $params,
                    'uses' => [
                        new ClosureUse($this->builder->var('config')),
                        new ClosureUse($this->builder->var('instantiator')),
                        new ClosureUse($this->builder->var('services')),
                        new ClosureUse($this->builder->var('providers'), byRef: true),
                        new ClosureUse($this->builder->var('flags')),
                    ],
                    'stmts' => [
                        ...$prepareDataStmts,
                        ...$instantiateStmts,
                    ],
                ]),
            )),
            ...$propertyValueProvidersStmts,
        ];
    }

    // TODO
    private function isNodeAlteringJson(DataModelNodeInterface $node, DecodeFrom $decodeFrom): bool
    {
        if (DecodeFrom::STRING === $decodeFrom) {
            return $node->isTransformed();
        }

        return $node->isTransformed() || !$node instanceof ScalarNode;
    }
}
