<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template;

use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;

/**
 * @internal
 */
abstract class NullTemplateGenerator
{
    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    abstract protected function null(array $context): array;

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    public function generate(array $context): array
    {
        return $this->null($context);
    }
}
