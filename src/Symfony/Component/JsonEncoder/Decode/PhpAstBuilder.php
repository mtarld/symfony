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

use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\Cast\Object_ as ObjectCast;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ClosureUse;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\Ternary;
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
use Symfony\Component\JsonEncoder\DataModel\Decode\DataModelNodeInterface;
use Symfony\Component\JsonEncoder\DataModel\Decode\ObjectNode;
use Symfony\Component\JsonEncoder\DataModel\Decode\ScalarNode;
use Symfony\Component\JsonEncoder\DataModel\PhpExprDataAccessor;
use Symfony\Component\JsonEncoder\Exception\LogicException;
use Symfony\Component\JsonEncoder\Instantiator\InstantiatorInterface;
use Symfony\Component\JsonEncoder\Instantiator\LazyInstantiatorInterface;
use Symfony\Component\JsonEncoder\PhpAstBuilderTrait;
use Symfony\Component\TypeInfo\Exception\LogicException as TypeInfoLogicException;
use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Symfony\Component\TypeInfo\Type\BuiltinType;
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
                    ...$this->buildEagerProviderStatements($dataModel, $context),
                    new Return_($this->builder->funcCall(new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($dataModel->getIdentifier())), [
                        $this->builder->staticCall(new FullyQualified(NativeDecoder::class), 'decodeString', [$this->builder->var('string'), $this->builder->var('flags')]),
                    ])),
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
                    ...$this->buildLazyProviderStatements($dataModel, $context),
                    new Return_($this->builder->funcCall(new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($dataModel->getIdentifier())), [
                        $this->builder->var('stream'),
                        $this->builder->val(0),
                        $this->builder->val(null),
                    ])),
                ],
            ]))],
        };
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<Stmt>
     */
    public function buildEagerProviderStatements(DataModelNodeInterface $dataModelNode, array &$context): array
    {
        if ($context['providers'][$dataModelNode->getIdentifier()] ?? false) {
            return [];
        }

        $context['providers'][$dataModelNode->getIdentifier()] = true;

        if (!$this->isNodeAlteringJson($dataModelNode)) {
            return [
                new Expression(new Assign(
                    new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($dataModelNode->getIdentifier())),
                    new Closure([
                        'static' => true,
                        'params' => [new Param($this->builder->var('data'))],
                        'stmts' => [new Return_($this->builder->var('data'))],
                    ]),
                )),
            ];
        }

        $originalType = $type = $dataModelNode->getType();
        try {
            $type = $type->asNonNullable();
        } catch (TypeInfoLogicException) {
        }

        if ($dataModelNode instanceof ScalarNode) {
            $accessor = match (true) {
                $type instanceof BackedEnumType => $this->builder->staticCall(
                    new FullyQualified($type->getClassName()),
                    $originalType->isNullable() ? 'tryFrom' : 'from',
                    [$this->builder->var('data')],
                ),
                $type->isA(TypeIdentifier::OBJECT) => new ObjectCast($this->builder->var('data')),
                default => $this->builder->var('data'),
            };

            return [
                new Expression(new Assign(
                    new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($dataModelNode->getIdentifier())),
                    new Closure([
                        'static' => true,
                        'params' => [new Param($this->builder->var('data'))],
                        'stmts' => [new Return_($accessor)],
                    ]),
                )),
            ];
        }

        if ($dataModelNode instanceof CollectionNode) {
            $returnNullStmts = $originalType->isNullable() ? [
                new If_(new Identical($this->builder->val(null), $this->builder->var('data')), [
                    'stmts' => [new Return_($this->builder->val(null))],
                ]),
            ] : [];

            $itemValueStmt = $this->isNodeAlteringJson($dataModelNode->item)
                ? $this->builder->funcCall(
                    new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($dataModelNode->item->getIdentifier())),
                    [$this->builder->var('v')],
                )
                : $this->builder->var('v');

            $iterableClosureStmts = [
                new Expression(new Assign(
                    $this->builder->var('iterable'),
                    new Closure([
                        'static' => true,
                        'params' => [new Param($this->builder->var('data'))],
                        'uses' => [
                            new ClosureUse($this->builder->var('config')),
                            new ClosureUse($this->builder->var('instantiator')),
                            new ClosureUse($this->builder->var('services')),
                            new ClosureUse($this->builder->var('providers'), byRef: true),
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

            $iterableValueStmt = $this->builder->funcCall($this->builder->var('iterable'), [$this->builder->var('data')]);
            $returnStmts = [new Return_($type->isA(TypeIdentifier::ARRAY) ? $this->builder->funcCall('\iterator_to_array', [$iterableValueStmt]) : $iterableValueStmt)];
            $providerStmts = $this->isNodeAlteringJson($dataModelNode->item) ? $this->buildEagerProviderStatements($dataModelNode->item, $context) : [];

            return [
                new Expression(new Assign(
                    new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($dataModelNode->getIdentifier())),
                    new Closure([
                        'static' => true,
                        'params' => [new Param($this->builder->var('data'))],
                        'uses' => [
                            new ClosureUse($this->builder->var('config')),
                            new ClosureUse($this->builder->var('instantiator')),
                            new ClosureUse($this->builder->var('services')),
                            new ClosureUse($this->builder->var('providers'), byRef: true),
                        ],
                        'stmts' => [...$returnNullStmts, ...$iterableClosureStmts, ...$returnStmts],
                    ]),
                )),
                ...$providerStmts,
            ];
        }

        if ($dataModelNode instanceof ObjectNode) {
            if ($dataModelNode->ghost) {
                return [];
            }

            $returnNullStmts = $originalType->isNullable() ? [
                new If_(new Identical($this->builder->val(null), $this->builder->var('data')), [
                    'stmts' => [new Return_($this->builder->val(null))],
                ]),
            ] : [];

            $propertyValueProvidersStmts = [];
            $propertiesValues = [];

            foreach ($dataModelNode->properties as $encodedName => $property) {
                $propertyValueProvidersStmts = [
                    ...$propertyValueProvidersStmts,
                    ...($this->isNodeAlteringJson($property['value']) ? $this->buildEagerProviderStatements($property['value'], $context) : []),
                ];

                $propertyValueStmt = $this->isNodeAlteringJson($property['value'])
                    ? new Ternary(
                        $this->builder->funcCall('\array_key_exists', [$this->builder->val($encodedName), $this->builder->var('data')]),
                        $this->builder->funcCall(
                            new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($property['value']->getIdentifier())),
                            [new ArrayDimFetch($this->builder->var('data'), $this->builder->val($encodedName))],
                        ),
                        $this->builder->val('_symfony_missing_value'),
                    )
                    : new Coalesce(new ArrayDimFetch($this->builder->var('data'), $this->builder->val($encodedName)), $this->builder->val('_symfony_missing_value'));

                $propertiesValues[] = new ArrayItem(
                    $this->convertDataAccessorToPhpExpr($property['accessor'](new PhpExprDataAccessor($propertyValueStmt))),
                    $this->builder->val($property['name']),
                );
            }

            return [
                new Expression(new Assign(
                    new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($dataModelNode->getIdentifier())),
                    new Closure([
                        'static' => true,
                        'params' => [new Param($this->builder->var('data'))],
                        'uses' => [
                            new ClosureUse($this->builder->var('config')),
                            new ClosureUse($this->builder->var('instantiator')),
                            new ClosureUse($this->builder->var('services')),
                            new ClosureUse($this->builder->var('providers'), byRef: true),
                        ],
                        'stmts' => [
                            ...$returnNullStmts,
                            new Return_($this->builder->methodCall($this->builder->var('instantiator'), 'instantiate', [
                                new ClassConstFetch(new FullyQualified($type->getClassName()), 'class'),
                                $this->builder->funcCall('\array_filter', [
                                    new Array_($propertiesValues, ['kind' => Array_::KIND_SHORT]),
                                    new Closure([
                                        'static' => true,
                                        'params' => [new Param($this->builder->var('v'))],
                                        'stmts' => [new Return_(new NotIdentical($this->builder->val('_symfony_missing_value'), $this->builder->var('v')))],
                                    ]),
                                ]),
                            ])),
                        ],
                    ]),
                )),
                ...$propertyValueProvidersStmts,
            ];
        }

        throw new LogicException(sprintf('Unexpected "%s" data model node', $dataModelNode::class));
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<Stmt>
     */
    public function buildLazyProviderStatements(DataModelNodeInterface $dataModelNode, array &$context): array
    {
        if ($context['providers'][$dataModelNode->getIdentifier()] ?? false) {
            return [];
        }

        $context['providers'][$dataModelNode->getIdentifier()] = true;

        $originalType = $type = $dataModelNode->getType();
        try {
            $type = $type->asNonNullable();
        } catch (TypeInfoLogicException) {
        }

        $prepareScalarNode = function (ScalarNode $node, Expr $offset, Expr $length): Node {
            $accessor = $this->builder->staticCall(new FullyQualified(NativeDecoder::class), 'decodeStream', [
                $this->builder->var('stream'),
                $offset,
                $length,
                $this->builder->var('flags'),
            ]);

            $originalType = $type = $node->getType();
            try {
                $type = $type->asNonNullable();
            } catch (TypeInfoLogicException) {
            }

            if ($type instanceof BackedEnumType) {
                return $this->builder->staticCall(
                    new FullyQualified($type->getClassName()),
                    $originalType->isNullable() ? 'tryFrom' : 'from',
                    [$accessor],
                );
            }

            if ($type instanceof BuiltinType && TypeIdentifier::OBJECT === $type->getTypeIdentifier()) {
                return new ObjectCast($accessor);
            }

            return $accessor;
        };

        if ($dataModelNode instanceof ScalarNode) {
            return [
                new Expression(new Assign(
                    new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($dataModelNode->getIdentifier())),
                    new Closure([
                        'static' => true,
                        'params' => [
                            new Param($this->builder->var('stream')),
                            new Param($this->builder->var('offset')),
                            new Param($this->builder->var('length')),
                        ],
                        'uses' => [new ClosureUse($this->builder->var('flags'))],
                        'stmts' => [new Return_($prepareScalarNode($dataModelNode, $this->builder->var('offset'), $this->builder->var('length'), $context))],
                    ]),
                )),
            ];
        }

        if ($dataModelNode instanceof CollectionNode) {
            $getBoundariesStmts = [
                new Expression(new Assign($this->builder->var('boundaries'), $this->builder->staticCall(
                    new FullyQualified(Splitter::class),
                    $type->isList() ? 'splitList' : 'splitDict',
                    [$this->builder->var('stream'), $this->builder->var('offset'), $this->builder->var('length')],
                ))),
            ];

            if ($originalType->isNullable()) {
                $getBoundariesStmts[] = new If_(new Identical($this->builder->val(null), $this->builder->var('boundaries')), [
                    'stmts' => [new Return_($this->builder->val(null))],
                ]);
            }

            $itemValueStmt = $dataModelNode->item instanceof ScalarNode
                ? $prepareScalarNode(
                    $dataModelNode->item,
                    new ArrayDimFetch($this->builder->var('boundary'), $this->builder->val(0)),
                    new ArrayDimFetch($this->builder->var('boundary'), $this->builder->val(1)),
                    $context,
                ) : $this->builder->funcCall(
                    new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($dataModelNode->item->getIdentifier())), [
                        $this->builder->var('stream'),
                        new ArrayDimFetch($this->builder->var('boundary'), $this->builder->val(0)),
                        new ArrayDimFetch($this->builder->var('boundary'), $this->builder->val(1)),
                    ],
                );

            $iterableClosureStmts = [
                new Expression(new Assign(
                    $this->builder->var('iterable'),
                    new Closure([
                        'static' => true,
                        'params' => [
                            new Param($this->builder->var('stream')),
                            new Param($this->builder->var('boundaries')),
                        ],
                        'uses' => [
                            new ClosureUse($this->builder->var('config')),
                            new ClosureUse($this->builder->var('instantiator')),
                            new ClosureUse($this->builder->var('services')),
                            new ClosureUse($this->builder->var('providers'), byRef: true),
                            new ClosureUse($this->builder->var('flags')),
                        ],
                        'stmts' => [
                            new Foreach_($this->builder->var('boundaries'), $this->builder->var('boundary'), [
                                'keyVar' => $this->builder->var('k'),
                                'stmts' => [new Expression(new Yield_($itemValueStmt, $this->builder->var('k')))],
                            ]),
                        ],
                    ]),
                )),
            ];

            $iterableValueStmt = $this->builder->funcCall($this->builder->var('iterable'), [$this->builder->var('stream'), $this->builder->var('boundaries')]);
            $returnStmts = [new Return_($type->isA(TypeIdentifier::ARRAY) ? $this->builder->funcCall('\iterator_to_array', [$iterableValueStmt]) : $iterableValueStmt)];
            $providerStmts = $dataModelNode->item instanceof ScalarNode ? [] : $this->buildLazyProviderStatements($dataModelNode->item, $context);

            return [
                new Expression(new Assign(
                    new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($dataModelNode->getIdentifier())),
                    new Closure([
                        'static' => true,
                        'params' => [
                            new Param($this->builder->var('stream')),
                            new Param($this->builder->var('offset')),
                            new Param($this->builder->var('length')),
                        ],
                        'uses' => [
                            new ClosureUse($this->builder->var('config')),
                            new ClosureUse($this->builder->var('instantiator')),
                            new ClosureUse($this->builder->var('services')),
                            new ClosureUse($this->builder->var('providers'), byRef: true),
                            new ClosureUse($this->builder->var('flags')),
                        ],
                        'stmts' => [...$getBoundariesStmts, ...$iterableClosureStmts, ...$returnStmts],
                    ]),
                )),
                ...$providerStmts,
            ];
        }

        if ($dataModelNode instanceof ObjectNode) {
            if ($dataModelNode->ghost) {
                return [];
            }

            $getBoundariesStmts = [
                new Expression(new Assign($this->builder->var('boundaries'), $this->builder->staticCall(
                    new FullyQualified(Splitter::class),
                    'splitDict',
                    [$this->builder->var('stream'), $this->builder->var('offset'), $this->builder->var('length')],
                ))),
            ];

            if ($originalType->isNullable()) {
                $getBoundariesStmts[] = new If_(new Identical($this->builder->val(null), $this->builder->var('boundaries')), [
                    'stmts' => [new Return_($this->builder->val(null))],
                ]);
            }

            $propertyValueProvidersStmts = [];
            $propertiesClosuresStmts = [];

            foreach ($dataModelNode->properties as $encodedName => $property) {
                $propertyValueProvidersStmts = [
                    ...$propertyValueProvidersStmts,
                    ...($property['value'] instanceof ScalarNode ? [] : $this->buildLazyProviderStatements($property['value'], $context)),
                ];

                $propertyValueStmt = $property['value'] instanceof ScalarNode
                    ? $prepareScalarNode(
                        $property['value'],
                        new ArrayDimFetch($this->builder->var('boundary'), $this->builder->val(0)),
                        new ArrayDimFetch($this->builder->var('boundary'), $this->builder->val(1)),
                        $context,
                    ) : $this->builder->funcCall(
                        new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($property['value']->getIdentifier())), [
                            $this->builder->var('stream'),
                            new ArrayDimFetch($this->builder->var('boundary'), $this->builder->val(0)),
                            new ArrayDimFetch($this->builder->var('boundary'), $this->builder->val(1)),
                        ],
                    );

                $propertiesClosuresStmts[] = new MatchArm([$this->builder->val($encodedName)], new Assign(
                    new ArrayDimFetch($this->builder->var('properties'), $this->builder->val($property['name'])),
                    new Closure([
                        'static' => true,
                        'uses' => [
                            new ClosureUse($this->builder->var('stream')),
                            new ClosureUse($this->builder->var('boundary')),
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

            $fillPropertiesArrayStmts = [
                new Expression(new Assign($this->builder->var('properties'), new Array_([], ['kind' => Array_::KIND_SHORT]))),
                new Foreach_($this->builder->var('boundaries'), $this->builder->var('boundary'), [
                    'keyVar' => $this->builder->var('k'),
                    'stmts' => [new Expression(new Match_(
                        $this->builder->var('k'),
                        [...$propertiesClosuresStmts, new MatchArm(null, $this->builder->val(null))],
                    ))],
                ]),
            ];

            $instantiateStmts = [
                new Return_($this->builder->methodCall($this->builder->var('instantiator'), 'instantiate', [
                    new ClassConstFetch(new FullyQualified($type->getClassName()), 'class'),
                    $this->builder->var('properties'),
                ])),
            ];

            return [
                new Expression(new Assign(
                    new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($dataModelNode->getIdentifier())),
                    new Closure([
                        'static' => true,
                        'params' => [
                            new Param($this->builder->var('stream')),
                            new Param($this->builder->var('offset')),
                            new Param($this->builder->var('length')),
                        ],
                        'uses' => [
                            new ClosureUse($this->builder->var('config')),
                            new ClosureUse($this->builder->var('instantiator')),
                            new ClosureUse($this->builder->var('services')),
                            new ClosureUse($this->builder->var('providers'), byRef: true),
                            new ClosureUse($this->builder->var('flags')),
                        ],
                        'stmts' => [
                            ...$getBoundariesStmts,
                            ...$fillPropertiesArrayStmts,
                            ...$instantiateStmts,
                        ],
                    ]),
                )),
                ...$propertyValueProvidersStmts,
            ];
        }

        throw new LogicException(sprintf('Unexpected "%s" data model node', $dataModelNode::class));
    }
}
