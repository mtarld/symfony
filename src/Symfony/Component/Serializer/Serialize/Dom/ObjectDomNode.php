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

use Symfony\Component\Serializer\Serialize\TemplateGenerator\NodeInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final readonly class ObjectDomNode extends DomNode
{
    /**
     * @param class-string $className
     * @param array<string, DomNode> $properties
     */
    public function  __construct(
        NodeInterface $accessor,
        public string $className,
        public array $properties,
    ) {
        parent::__construct($accessor);
    }

    public function toArray(): array
    {
        return [
        ];
    }
}
