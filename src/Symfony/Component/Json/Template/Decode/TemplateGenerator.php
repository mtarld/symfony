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
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use Symfony\Component\Encoder\DataModel\Decode\CollectionNode;
use Symfony\Component\Encoder\DataModel\Decode\DataModelNodeInterface;
use Symfony\Component\Encoder\DataModel\Decode\ObjectNode;
use Symfony\Component\Encoder\DataModel\Decode\ScalarNode;
use Symfony\Component\Encoder\Exception\LogicException;
use Symfony\Component\Json\Template\PhpExprDataAccessor;
use Symfony\Component\Json\Template\TemplateGeneratorTrait;

/**
 * Generates a template PHP syntax tree that decodes data.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type JsonDecodeConfig from JsonDecoder
 */
final readonly class TemplateGenerator
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
                $this->builder->staticCall(new FullyQualified(Decoder::class), 'decodeString', [$this->builder->var('string'), $this->builder->var('flags')]),
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
            !$this->isNodeAlteringJson($node) => $this->getRawJsonStmts($node),
            $node instanceof CollectionNode => $this->getCollectionStmts($node, $context),
            $node instanceof ObjectNode => $this->getObjectStmts($node, $context),
            $node instanceof ScalarNode => $this->getScalarStmts($node),
            default => throw new LogicException(sprintf('Unexpected "%s" node', $node::class)),
        };
    }

    /**
     * @return list<Stmt>
     */
    private function getRawJsonStmts(DataModelNodeInterface $node): array
    {
        return [
            new Expression(new Assign(
                new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($node->getIdentifier())),
                new Closure([
                    'static' => true,
                    'params' => [new Param($this->builder->var('data'))],
                    'stmts' => [new Return_($this->builder->var('data'))],
                ]),
            )),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<Stmt>
     */
    private function getCollectionStmts(CollectionNode $node, array &$context): array
    {
        $returnNullStmts = $node->getType()->isNullable() ? [
            new If_(new Identical($this->builder->val(null), $this->builder->var('data')), [
                'stmts' => [new Return_($this->builder->val(null))],
            ]),
        ] : [];

        $itemValueStmt = $this->isNodeAlteringJson($node->item)
            ? $this->builder->funcCall(
                new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($node->item->getIdentifier())),
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

        $returnStmts = [new Return_('array' === $node->getType()->getBuiltinType() ? $this->builder->funcCall('\iterator_to_array', [$iterableValueStmt]) : $iterableValueStmt)];

        $providerStmts = $this->isNodeAlteringJson($node->item) ? $this->getProviderStmts($node->item, $context) : [];

        return [
            new Expression(new Assign(
                new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($node->getIdentifier())),
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

        $returnNullStmts = $node->getType()->isNullable() ? [
            new If_(new Identical($this->builder->val(null), $this->builder->var('data')), [
                'stmts' => [new Return_($this->builder->val(null))],
            ]),
        ] : [];

        $propertyValueProvidersStmts = [];
        $propertiesValues = [];

        foreach ($node->properties as $encodedName => $property) {
            $propertyValueProvidersStmts = [
                ...$propertyValueProvidersStmts,
                ...($this->isNodeAlteringJson($property['value']) ? $this->getProviderStmts($property['value'], $context) : []),
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
                new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($node->getIdentifier())),
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
                            new ClassConstFetch(new FullyQualified($node->getType()->getClassName()), 'class'),
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

    /**
     * @return list<Stmt>
     */
    private function getScalarStmts(ScalarNode $node): array
    {
        $accessor = match (true) {
            $node->getType()->isBackedEnum() => $this->builder->staticCall(
                new FullyQualified($node->getType()->getClassName()),
                $node->getType()->isNullable() ? 'tryFrom' : 'from',
                [$this->builder->var('data')],
            ),
            $node->getType()->isObject() => new ObjectCast($this->builder->var('data')),
            default => $this->builder->var('data'),
        };

        return [
            new Expression(new Assign(
                new ArrayDimFetch($this->builder->var('providers'), $this->builder->val($node->getIdentifier())),
                new Closure([
                    'static' => true,
                    'params' => [new Param($this->builder->var('data'))],
                    'stmts' => [new Return_($accessor)],
                ]),
            )),
        ];
    }
}
