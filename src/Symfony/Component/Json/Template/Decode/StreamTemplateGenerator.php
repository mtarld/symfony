<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json\Template\Decode;

use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\Cast\Object_ as ObjectCast;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ClosureUse;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Continue_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use Symfony\Component\Encoder\DataModel\Decode\CollectionNode;
use Symfony\Component\Encoder\DataModel\Decode\DataModelNodeInterface;
use Symfony\Component\Encoder\DataModel\Decode\ObjectNode;
use Symfony\Component\Encoder\DataModel\Decode\ScalarNode;
use Symfony\Component\Encoder\Exception\LogicException;
use Symfony\Component\Json\JsonDecoder;
use Symfony\Component\Json\Template\PhpExprDataAccessor;
use Symfony\Component\Json\Template\TemplateGeneratorTrait;

/**
 * Generates a template PHP syntax tree that decodes data lazily.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type JsonDecodeConfig from JsonDecoder
 */
final readonly class StreamTemplateGenerator
{
    use TemplateGeneratorTrait;

    public function __construct()
    {
        $this->builder = new BuilderFactory();
    }

    /**
     * @param JsonDecodeConfig     $config
     * @param array<string, mixed> $context
     *
     * @return list<Stmt>
     */
    public function generate(DataModelNodeInterface $node, array $config, array $context): array
    {
        return [
            new Expression(new Assign(
                $this->builder->var('flags'),
                new Coalesce(new ArrayDimFetch($this->builder->var('config'), $this->builder->val('json_decode_flags')), $this->builder->val(0)),
            )),
            ...$this->getProviderStmts($node, $context),
            new Return_($this->builder->funcCall(new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($node->getIdentifier())), [
                $this->builder->var('stream'),
                $this->builder->val(0),
                $this->builder->val(null),
            ])),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<Stmt>
     */
    private function getProviderStmts(DataModelNodeInterface $node, array &$context): array
    {
        if ($context['providers'][$node->getIdentifier()] ?? false) {
            return [];
        }

        $context['providers'][$node->getIdentifier()] = true;

        return match (true) {
            $node instanceof CollectionNode => $this->getCollectionStmts($node, $context),
            $node instanceof ObjectNode => $this->getObjectStmts($node, $context),
            $node instanceof ScalarNode => $this->getScalarStmts($node, $context),
            default => throw new LogicException(sprintf('Unexpected "%s" node', $node::class)),
        };
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<Stmt>
     */
    private function getCollectionStmts(CollectionNode $node, array &$context): array
    {
        $getBoundariesStmts = [
            new Expression(new Assign($this->builder->var('boundaries'), $this->builder->staticCall(
                new FullyQualified(Splitter::class),
                $node->getType()->isList() ? 'splitList' : 'splitDict',
                [$this->builder->var('stream'), $this->builder->var('offset'), $this->builder->var('length')],
            ))),
        ];

        if ($node->getType()->isNullable()) {
            $getBoundariesStmts[] = new If_(new Identical($this->builder->val(null), $this->builder->var('boundaries')), [
                'stmts' => [new Return_($this->builder->val(null))],
            ]);
        }

        $itemValueStmt = $node->item instanceof ScalarNode
            ? $this->prepareScalarNode(
                $node->item,
                new ArrayDimFetch($this->builder->var('boundary'), $this->builder->val(0)),
                new ArrayDimFetch($this->builder->var('boundary'), $this->builder->val(1)),
                $context,
            ) : $this->builder->funcCall(
                new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($node->item->getIdentifier())), [
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
                        new Param($this->builder->var('stream'), type: 'mixed'),
                        new Param($this->builder->var('boundaries'), type: 'iterable'),
                    ],
                    'returnType' => 'iterable',
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

        $returnStmts = [new Return_('array' === $node->getType()->getBuiltinType() ? $this->builder->funcCall('\iterator_to_array', [$iterableValueStmt]) : $iterableValueStmt)];

        $providerStmts = $node->item instanceof ScalarNode ? [] : $this->getProviderStmts($node->item, $context);

        return [
            new Expression(new Assign(
                new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($node->getIdentifier())),
                new Closure([
                    'static' => true,
                    'params' => [
                        new Param($this->builder->var('stream'), type: 'mixed'),
                        new Param($this->builder->var('offset'), type: 'int'),
                        new Param($this->builder->var('length'), type: '?int'),
                    ],
                    'returnType' => ($node->getType()->isNullable() ? '?' : '').$node->getType()->getBuiltinType(),
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

    /**
     * @param array<string, mixed> $context
     *
     * @return list<Stmt>
     */
    private function getObjectStmts(ObjectNode $node, array &$context): array
    {
        if ($node->ghost) {
            return [];
        }

        $getBoundariesStmts = [
            new Expression(new Assign($this->builder->var('boundaries'), $this->builder->staticCall(
                new FullyQualified(Splitter::class),
                'splitDict',
                [$this->builder->var('stream'), $this->builder->var('offset'), $this->builder->var('length')],
            ))),
        ];

        if ($node->getType()->isNullable()) {
            $getBoundariesStmts[] = new If_(new Identical($this->builder->val(null), $this->builder->var('boundaries')), [
                'stmts' => [new Return_($this->builder->val(null))],
            ]);
        }

        $propertyValueProvidersStmts = [];
        $propertiesClosuresStmts = [];

        foreach ($node->properties as $encodedName => $property) {
            $propertyValueProvidersStmts = [
                ...$propertyValueProvidersStmts,
                ...($property['value'] instanceof ScalarNode ? [] : $this->getProviderStmts($property['value'], $context)),
            ];

            $propertyValueStmt = $property['value'] instanceof ScalarNode
                ? $this->prepareScalarNode(
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

            $propertiesClosuresStmts[] = new If_(new Identical($this->builder->val($encodedName), $this->builder->var('k')), [
                'stmts' => [
                    new Expression(new Assign(
                        new ArrayDimFetch($this->builder->var('properties'), $this->builder->val($property['name'])),
                        new Closure([
                            'static' => true,
                            'returnType' => 'mixed',
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
                    )),
                    new Continue_(),
                ],
            ]);
        }

        $fillPropertiesArrayStmts = [
            new Expression(new Assign($this->builder->var('properties'), new Array_([], ['kind' => Array_::KIND_SHORT]))),
            new Foreach_($this->builder->var('boundaries'), $this->builder->var('boundary'), [
                'keyVar' => $this->builder->var('k'),
                'stmts' => $propertiesClosuresStmts,
            ]),
        ];

        $instantiateStmts = [
            new Return_($this->builder->methodCall($this->builder->var('instantiator'), 'instantiate', [
                new ClassConstFetch(new FullyQualified($node->getType()->getClassName()), 'class'),
                $this->builder->var('properties'),
            ])),
        ];

        return [
            new Expression(new Assign(
                new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($node->getIdentifier())),
                new Closure([
                    'static' => true,
                    'params' => [
                        new Param($this->builder->var('stream'), type: 'mixed'),
                        new Param($this->builder->var('offset'), type: 'int'),
                        new Param($this->builder->var('length'), type: '?int'),
                    ],
                    'returnType' => $node->getType()->isNullable()
                        ? new NullableType(new FullyQualified($node->getType()->getClassName()))
                        : new FullyQualified($node->getType()->getClassName()),
                    'uses' => [
                        new ClosureUse($this->builder->var('config')),
                        new ClosureUse($this->builder->var('instantiator')),
                        new ClosureUse($this->builder->var('services')),
                        new ClosureUse($this->builder->var('providers'), byRef: true),
                        new ClosureUse($this->builder->var('flags')),
                    ],
                    'stmts' => [...$getBoundariesStmts, ...$fillPropertiesArrayStmts, ...$instantiateStmts],
                ]),
            )),
            ...$propertyValueProvidersStmts,
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<Stmt>
     */
    private function getScalarStmts(ScalarNode $node, array &$context): array
    {
        return [
            new Expression(new Assign(
                new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($node->getIdentifier())),
                new Closure([
                    'static' => true,
                    'returnType' => 'mixed',
                    'params' => [
                        new Param($this->builder->var('stream'), type: 'mixed'),
                        new Param($this->builder->var('offset'), type: 'int'),
                        new Param($this->builder->var('length'), type: '?int'),
                    ],
                    'uses' => [new ClosureUse($this->builder->var('flags'))],
                    'stmts' => [new Return_($this->prepareScalarNode($node, $this->builder->var('offset'), $this->builder->var('length'), $context))],
                ]),
            )),
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    private function prepareScalarNode(ScalarNode $node, Expr $offset, Expr $length, array $context): Node
    {
        $accessor = $this->builder->staticCall(new FullyQualified(Decoder::class), 'decodeStream', [
            $this->builder->var('stream'),
            $offset,
            $length,
            $this->builder->var('flags'),
        ]);

        if ($node->getType()->isBackedEnum()) {
            return $this->builder->staticCall(
                new FullyQualified($node->getType()->getClassName()),
                $node->getType()->isNullable() ? 'tryFrom' : 'from',
                [$accessor],
            );
        }

        if ($node->getType()->isObject()) {
            return new ObjectCast($accessor);
        }

        return $accessor;
    }
}
