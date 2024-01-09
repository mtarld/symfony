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
use PhpParser\Node\Stmt\Expression;
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
    /** @var list<array{buffer: string, toMerge: string}> */
    private array $stack = [['toMerge' => '', 'buffer' => '']];
    private int $index = 0;

    /** @var array<string, array{buffer: string, toMerge: string}> */
    private array $strings = [];

    abstract protected function isMergeableNode(Node $node): bool;

    abstract protected function getStringToMerge(Node $node): string;

    abstract protected function getMergedNode(string $merged): Stmt;

    final public function enterNode(Node $node): int|Node|array|null
    {
        if ($this->isBranchingNode($node) || $node instanceof Expression) {
            dump($this->index.' > '.$node::class);
        }

        if ($this->isBranchingNode($node)) {
            $this->stack[$this->index]['toMerge'] = $this->stack[$this->index]['buffer'];
            $this->stack[$this->index]['buffer'] = '';

            ++$this->index;

            dump('override');
            $this->stack[$this->index] = ['toMerge' => '', 'buffer' => ''];

            return null;
        }

        if ($this->isMergeableNode($node)) {
            $this->stack[$this->index]['buffer'] .= $this->getStringToMerge($node);

            return null;
        }

        if ($node instanceof Expression && '' !== $this->stack[$this->index]['buffer']) {
            $this->stack[$this->index]['toMerge'] = $this->stack[$this->index]['buffer'];
            $this->stack[$this->index]['buffer'] = '';

            return null;
        }

        return null;
    }

    final public function leaveNode(Node $node): int|Node|array|null
    {
        if ($this->isBranchingNode($node) || $node instanceof Expression) {
            dump($this->index.' < '.$node::class);
        }

        if ($this->isMergeableNode($node)) {
            return NodeTraverser::REMOVE_NODE;
        }

        $string = $this->stack[$this->index]['toMerge'];

        if ('' !== $string && $this->canAddNode($node)) {
            $this->stack[$this->index]['toMerge'] = '';

            return $this->addNode($node, $string);
        }

        if ($this->isBranchingNode($node)) {
            if ('' !== $this->stack[$this->index]['buffer']) {
                $this->stack[$this->index]['toMerge'] = $this->stack[$this->index]['buffer'];
                $this->stack[$this->index]['buffer'] = '';
            }

            if ($node instanceof ElseIf_) {
                dump($this->stack, $this->index);
            }

            $string = $this->stack[$this->index]['toMerge'];

            if ('' !== $string && $this->canAddNode($node)) {
                $this->stack[$this->index]['toMerge'] = '';
                --$this->index;

                return $this->addNode($node, $string);
            }

            --$this->index;
        }

        return null;
    }

    private function canAddNode(Node $node): bool
    {
        return $node instanceof Expression | $this->isBranchingNode($node);
    }

    private function addNode(Node $node, string $string): Node|array
    {
        $mergedNode = $this->getMergedNode($string);

        if ($this->isBranchingNode($node)) {
            $node->stmts[] = $mergedNode;

            return $node;
        }

        if ($node instanceof Expression) {
            return [$mergedNode, $node];
        }

        return $node;
    }

    private function isBranchingNode(Node $node): bool
    {
        return \in_array($node::class, [
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
        ], true);
    }
}
