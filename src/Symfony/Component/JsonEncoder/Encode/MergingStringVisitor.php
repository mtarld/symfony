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

use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Case_;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\ElseIf_;
use PhpParser\Node\Stmt\Finally_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\Node\Stmt\While_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Abstraction that merges strings which are written
 * consequently to reduce the instructions amount.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
abstract class MergingStringVisitor extends NodeVisitorAbstract
{
    private const BRANCHING_NODE_CLASSES = [
        Catch_::class,
        Case_::class,
        ClassMethod::class,
        Closure::class,
        Function_::class,
        Do_::class,
        Else_::class,
        ElseIf_::class,
        For_::class,
        Foreach_::class,
        Finally_::class,
        If_::class,
        TryCatch::class,
        While_::class,
    ];

    /**
     * @var list<string>
     */
    private array $merged = [];

    private int $branchIndex = 0;

    abstract protected function isMergeableNode(Node $node): bool;

    abstract protected function getStringToMerge(Node $node): string;

    abstract protected function getMergedNode(string $merged): Stmt;

    final public function enterNode(Node $node): int|Node|array|null
    {
        if ($this->isBranchingNode($node)) {
            ++$this->branchIndex;
            $this->merged[$this->branchIndex] = '';
        }

        return null;
    }

    final public function leaveNode(Node $node): int|Node|array|null
    {
        if ($this->isMergeableNode($node)) {
            $this->merged[$this->branchIndex] .= $this->getStringToMerge($node);

            return NodeTraverser::REMOVE_NODE;
        }

        if ($this->isBranchingNode($node)) {
            $merged = $this->merged[$this->branchIndex] ?? '';
            $this->merged[$this->branchIndex] = '';
            --$this->branchIndex;

            if ('' === $merged) {
                return null;
            }

            $node->stmts[] = $this->getMergedNode($merged);

            return $node;
        }

        if ($node instanceof Stmt) {
            $merged = $this->merged[$this->branchIndex] ?? '';
            $this->merged[$this->branchIndex] = '';

            if ('' === $merged) {
                return null;
            }

            return [$this->getMergedNode($merged), $node];
        }

        return null;
    }

    private function isBranchingNode(Node $node): bool
    {
        return \in_array($node::class, self::BRANCHING_NODE_CLASSES, true);
    }
}
