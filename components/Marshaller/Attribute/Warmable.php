<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Attribute;

use Symfony\Component\Marshaller\Context\Context;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Warmable
{
    /**
     * @param list<Context> $contexts
     */
    public function __construct(
        public readonly array $contexts = [],
    ) {
        foreach ($this->contexts as $context) {
            if (!$context instanceof Context) {
                throw new \InvalidArgumentException(sprintf('Parameter "contexts" of attribute "%s" must only contains "%s" instances.', self::class, Context::class));
            }
        }
    }
}
