<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template;

use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Native\Type\Type;

/**
 * @internal
 */
abstract class ScalarTemplateGenerator
{
    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    abstract protected function scalar(Type $type, NodeInterface $accessor, array $context): array;

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    public function generate(Type $type, NodeInterface $accessor, array $context): array
    {
        return $this->scalar($type, $accessor, $context);
    }
}
