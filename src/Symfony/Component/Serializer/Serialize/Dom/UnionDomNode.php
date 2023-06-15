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
final readonly class UnionDomNode extends DomNode
{
    /**
     * @param list<DomNode> $domNodes
     */
    public function  __construct(
        string $accessor,
        public array $domNodes,
    ) {
        parent::__construct($accessor);
    }

    public function toArray(): array
    {
        return [
            'domNodes' => array_map(fn (DomNode $p): array => $p->toArray(), $this->domNodes),
        ] + parent::toArray();
    }
}
