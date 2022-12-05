<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Template;

use Symfony\Component\Marshaller\Internal\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Internal\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Internal\Type\Type;

/**
 * @internal
 */
final class ScalarTemplateGenerator
{
    /**
     * @param \Closure(NodeInterface): NodeInterface $valueEscaper
     */
    public function __construct(
        private readonly \Closure $valueEscaper,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    public function generate(Type $type, NodeInterface $accessor, array $context): array
    {
        return [
            new ExpressionNode(new FunctionNode('\fwrite', [new VariableNode('resource'), ($this->valueEscaper)($accessor)])),
        ];
    }
}
