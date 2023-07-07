<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Dom;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final readonly class ObjectDomNode extends DomNode
{
    /**
     * @param class-string           $className
     * @param array<string, DomNode> $properties
     */
    public function __construct(
        string $accessor,
        public string $className,
        public array $properties,
    ) {
        parent::__construct($accessor);
    }

    public function toArray(): array
    {
        return [
            'className' => $this->className,
            'properties' => array_map(fn (DomNode $p): array => $p->toArray(), $this->properties),
        ] + parent::toArray();
    }
}
