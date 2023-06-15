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
final readonly class ValueDomNode extends DomNode
{
    public function  __construct(
        string $accessor,
        public string $type,
    ) {
        parent::__construct($accessor);
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
        ] + parent::toArray();
    }
}
