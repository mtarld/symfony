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
final readonly class Node
{
    /**
     * @param list<self> $children
     */
    public function  __construct(
        public string $name,
        public string $type,
        public string $accessor,
        public array $children = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'accessor' => $this->accessor,
            'children' => array_map($this->toArray(...), $this->children),
        ];
    }
}
