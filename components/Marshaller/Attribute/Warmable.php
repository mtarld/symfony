<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Attribute;

use Symfony\Component\Marshaller\Context\Context;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Warmable
{
    /**
     * @param list<Context> $enforcedContexts
     */
    public function __construct(
        public readonly array $enforcedContexts = [],
    ) {
        foreach ($this->enforcedContexts as $enforcedContext) {
            if (!$enforcedContext instanceof Context) {
                throw new \InvalidArgumentException(sprintf('Parameter "enforcedContexts" of attribute "%s" must only contains "%s" instances.', self::class, Context::class));
            }
        }
    }
}
